import json
import re
import sys
from os import path, listdir
from abc import ABC, abstractmethod
import traceback as tb
from subprocess import Popen, PIPE

from typing import Any, Iterable

WARNCOUNT: dict[str, int] = {}
CURRENT_SERVICE: str | None = None

def warn(msg: str, *context: Any):
    """Prints a warning message to the console and increments the global WARNCOUNT variable.

    :param str msg: The warning message to print.
    :param Any *context: Any contextual info to be passed along.
    """
    WARN = "\033[0m\033[43m\033[30mWARN:\033[0m "
    WARN_TAB = "\033[0m    \033[43m\033[33m|\033[0m "
    WARN_TAB_LAST = "\033[0m    \033[43m\033[33m\033[58;5;0m\033[4m|\033[0m "

    WARNCOUNT[msg] = (WARNCOUNT.get(msg) or 0) + 1

    stack = tb.extract_stack()
    stack_str = " -> ".join([f"\033[34m{frame.name}\033[0m" for frame in stack if frame != stack[-1]])

    service_msg: str
    if CURRENT_SERVICE is None:
        service_msg = ""
    else:
        service_msg = f"in service \033[36m{CURRENT_SERVICE}\033[0m "

    context = tuple(f"{c}".strip() for c in context)
    context_formatted = [f"\n{c}\033[0m".replace('\n', f"\n\033[0m{WARN_TAB}  \033[2m") for c in context]
    if len(context_formatted) > 0:
        context_formatted[-1] = context_formatted[-1].replace(WARN_TAB, WARN_TAB_LAST)

    print(
        f"{WARN}\033[31m{msg}\033[0m {service_msg}({stack_str})",
        *context_formatted,
        file=sys.stderr,
        sep=""
    )

def convert_php_type_to_normal_type(php_type: str) -> tuple[str, bool]:
    CONVERSIONS = {
        "int": "int",
        "string": "String",
        "bool": "bool",
    }

    if php_type[0] == '?':
        nullable = True
        php_type = php_type[1:]
    else:
        nullable = False

    if php_type in CONVERSIONS.keys():
        return CONVERSIONS[php_type], nullable
    else:
        warn("unrecognized php datatype", php_type)
        return php_type, nullable

def convert_moodle_type_to_normal_type(param_type: str) -> str:
    CONVERSIONS = {
        "PARAM_INT": "int",
        "PARAM_TEXT": "String",
        "PARAM_URL": "String",
        "PARAM_BOOL": "bool",
    }

    return CONVERSIONS.get(param_type, param_type)

def explain_php_value(val: str) -> tuple[None | str | int | bool, str]:
    SPECIAL_VALUES = {
        "$USER->id": ("derived from token", "int"), # this exact phrase is checked for in the web UI
        "null": (None, "")
    }

    if val in SPECIAL_VALUES.keys():
        return SPECIAL_VALUES[val]
    elif val.isnumeric():
        return (int(val), "int")
    elif val.lower() in ("true", "false"):
        return (val.lower() == "true", "bool")
    elif val[0] + val[-1] in ("''", '""'):

        # see if the same kind of quote is within string
        if val[1:-1].count(val[0]) > 0:
            warn("found potentially non-literal string", val)

        return (val[1:-1], "String")
    else:
        warn("found unknown value", val)
        return (f"unknown: {val}", "unknown")

def parse_isrequired(inpot: str) -> bool | None:
    if inpot in ('VALUE_REQUIRED', ''):
        return True
    elif inpot == 'VALUE_DEFAULT':
        return False
    else:
        warn("found unparseable value for isrequired", inpot)
        return None

def parse_nullable(inpot: str) -> bool | None:
    if inpot in ('', 'NULL_NOT_ALLOWED'):
        return False
    elif inpot == 'NULL_ALLOWED':
        return True
    else:
        warn("found weird value for nullable", inpot)
        return None

class PHPNameResolution:
    __slots__ = ('namespace', 'imports')
    namespace: str | None
    imports: list[str]

    def __init__(self, namespace: str | None, imports: list[str]):
        self.namespace = namespace
        self.imports = imports

    def __str__(self) -> str:
        statements = []
        if self.namespace is not None:
            statements.append(f"namespace local_lbplanner\\{self.namespace};")
        for use in self.imports:
            statements.append(f"use local_lbplanner\\{use}")
        return " ".join(statements)

class PHPExpression(ABC):
    @abstractmethod
    def __str__(self) -> str:
        raise NotImplementedError()

class PHPString(PHPExpression, ABC):
    @abstractmethod
    def get_value(self) -> str:
        raise NotImplementedError()

class PHPStringLiteral(PHPString):
    __slots__ = ('value')
    value: str

    def __init__(self, val: str):
        self.value = val

    def __str__(self) -> str:
        return f"'{self.value.replace('\'', '\\\'')}'"

    def get_value(self) -> str:
        return self.value

class PHPConcat(PHPString):
    __slots__ = ('left', 'right')
    left: PHPString
    right: PHPString

    def __init__(self, left: PHPString, right: PHPString):
        self.left = left
        self.right = right

    def __str__(self) -> str:
        return f"{self.left}.{self.right}"

    def get_value(self) -> str:
        return self.left.get_value() + self.right.get_value()

class PHPUserID(PHPExpression):
    def __str__(self) -> str:
        return "$USER->id"

class PHPArray(PHPExpression):
    __slots__ = ('keys', 'values')

    keys: list[PHPString] | None
    values: list[PHPExpression]

    def __init__(self, vals: list[PHPExpression], keys: list[PHPString] | None = None):
        self.keys = keys
        self.values = vals

    def __str__(self) -> str:
        inner: Iterable[str]
        if self.keys is not None:
            inner = (f"{k} => {v}" for k, v in zip(self.keys, self.values))
        else:
            inner = (str(v) for v in self.values)
        return '[' + ", ".join(inner) + ']'

class PHPClassMemberFunction(PHPExpression):
    __slots__ = ('classname', 'funcname', 'fp')
    classname: str
    funcname: str
    fp: str | None

    def __init__(self, classname: str, funcname: str, fp: str | None):
        self.classname = classname
        self.funcname = funcname
        self.fp = fp

    def resolve(self) -> PHPExpression:
        meth_pattern = rf"public static function {self.funcname}\(\)(?: ?: ?\w+)? ?{{(?P<body>.*?)}}"

        if self.fp is None:
            # already warned in parse_imports, we don't need to warn again
            return PHPConstant('null')

        with open(self.fp, "r") as f:
            new_file_content = f.read()

        meth_matches: list[str] = re.findall(meth_pattern, new_file_content, re.DOTALL)
        if len(meth_matches) == 0:
            warn("Missing class member", f"couldn't find {self} inside {self.fp}")
            return PHPConstant('null')
        elif len(meth_matches) > 1:
            raise Exception(f"Found multiple definitions for {self} inside {self.fp}")
        else:
            imports = extract_imports(new_file_content)
            result = parse_code(meth_matches[0], imports)

            return result

    def __str__(self) -> str:
        return f"{self.classname}::{self.funcname}()"

class PHPEnum():
    @classmethod
    def getcases(cls, classname: str) -> dict[str, str]:
        # https://regex101.com/r/p5FzCh
        casepattern = r"const (\w+) = (\d+|true|false|(['\"]).*?\3)"

        fullbody_pattern = f"class {classname} extends (Enum|\\w+) {{(.*?)}}"

        cases = {}

        fp = f"lbplanner/classes/enums/{classname}.php"
        if not path.exists(fp):
            warn("Couldn't find enum file", fp)
            return {}
        with open(fp, "r") as f:
            matches: list[list[str]] = re.findall(fullbody_pattern, f.read(), re.DOTALL)
            if len(matches) == 1:
                if matches[0][0] != 'Enum':
                    cases = cls.getcases(matches[0][0])
                body = matches[0][1]
            else:
                warn("couldn't parse enum", f"name: {classname}", matches)

        matches2: list[str] = re.findall(casepattern, body)
        for match in matches2:
            val = match[1].replace("'", '"')
            cases[match[0]] = val

        return cases

class PHPEnumCase(PHPEnum, PHPString):
    __slots__ = ('classname', 'casename', 'fp')
    classname: str
    casename: str
    fp: str

    def __init__(self, classname: str, casename: str, fp: str):
        self.classname = classname
        self.casename = casename
        self.fp = fp

    def resolve(self) -> PHPString:
        cases = self.getcases(self.classname)
        if self.casename not in cases.keys():
            warn("enum member not found", f"{self.classname}::{self.casename}", cases)
            return PHPStringLiteral("?")

        val = cases[self.casename]

        if val.startswith('"') and val.endswith('"'):
            val = val[1:-1]

        return PHPStringLiteral(val)

    def get_value(self) -> str:
        return self.resolve().get_value()

    def __str__(self) -> str:
        return f"{self.classname}::{self.casename}"

class PHPEnumFormat(PHPEnum, PHPClassMemberFunction, PHPString):
    def resolve(self) -> PHPString:
        cases = self.getcases(self.classname)
        # capitalizing first letter of each key
        cases = {"".join([name[0].upper(), name[1:].lower()]): case for name, case in cases.items()}

        return PHPStringLiteral("{ " + ", ".join([f"{name} = {value}" for name, value in cases.items()]) + " }")

    def get_value(self) -> str:
        return self.resolve().get_value()

class PHPConstructor(PHPExpression):
    __slots__ = ('name', 'parameters')

    name: str
    parameters: list[PHPExpression]

    def __init__(self, name: str, params: list[PHPExpression]):
        self.name = name
        self.parameters = params

    def __str__(self) -> str:
        return f"new {self.name}(" + ", ".join(str(p) for p in self.parameters) + ")"

    def toIR(self) -> 'IRElement':
        match self.name:
            case 'external_function_parameters' | 'external_single_structure':
                assert isinstance(self.parameters[0], PHPArray)
                arr = self.parameters[0]
                fields = {}
                if len(arr.values) != 0:
                    assert arr.keys is not None
                    for k, v in zip(arr.keys, arr.values):
                        assert isinstance(v, PHPConstructor)
                        fields[k.get_value()] = v.toIR()

                desc = ""
                if len(self.parameters) >= 2:
                    assert isinstance(self.parameters[1], PHPString)
                    desc = self.parameters[1].get_value()

                required = True
                if len(self.parameters) >= 3:
                    assert isinstance(self.parameters[2], PHPConstant)
                    _required = parse_isrequired(self.parameters[2].name)
                    if _required is not None:
                        required = _required

                return IRObject(fields, description=desc, required=required)
            case 'external_multiple_structure':
                assert isinstance(self.parameters[0], PHPConstructor)
                con = self.parameters[0]

                desc = ""
                if len(self.parameters) >= 2:
                    assert isinstance(self.parameters[1], PHPString)
                    desc = self.parameters[1].get_value()

                required = True
                if len(self.parameters) >= 3:
                    assert isinstance(self.parameters[2], PHPConstant)
                    _required = parse_isrequired(self.parameters[2].name)
                    if _required is not None:
                        required = _required

                return IRArray(con.toIR(), description=desc, required=required)
            case 'external_value':
                if len(self.parameters) < 2:
                    warn("found external_value with not enough parameters", self.parameters)
                    return IRValue(None, None, nullable=True, description="", required=True)
                assert isinstance(self.parameters[0], PHPConstant)
                assert isinstance(self.parameters[1], PHPString)
                typ = convert_moodle_type_to_normal_type(self.parameters[0].name)
                desc = self.parameters[1].get_value()

                required = True
                if len(self.parameters) >= 3:
                    assert isinstance(self.parameters[2], PHPConstant)
                    _required = parse_isrequired(self.parameters[2].name)
                    if _required is not None:
                        required = _required

                default: None | bool | str = None
                if len(self.parameters) >= 4:
                    if isinstance(self.parameters[3], PHPConstant):
                        match self.parameters[3].name:
                            case 'null':
                                default = None
                            case 'false':
                                default = False
                            case 'true':
                                default = True
                            case _:
                                warn("unknown PHPConstant as default", self.parameters[3])
                                default = None
                    elif isinstance(self.parameters[3], PHPUserID):
                        default = "derived from token"

                nullable = False
                if len(self.parameters) >= 5:
                    assert isinstance(self.parameters[4], PHPConstant)
                    _nullable = parse_nullable(self.parameters[4].name)
                    if _nullable is not None:
                        nullable = _nullable

                return IRValue(typ, default_value=default, nullable=nullable, description=desc, required=required)
            case _:
                warn("unkown constructor name", self.name)
                return IRValue(None, None, nullable=True)

class PHPConstant(PHPExpression):
    __slots__ = ('name')

    name: str

    def __init__(self, name: str):
        self.name = name

    def __str__(self) -> str:
        return self.name

class SlotsDict:
    @property
    def __dict__(self):
        return {name: self.__getattribute__(name) for name in self.get_slots()}

    def get_slots(self):
        slots = tuple()
        for cls in self.__class__.__mro__:
            if cls != SlotsDict and issubclass(cls, SlotsDict):
                slots = cls.__slots__ + slots

        return slots

class FunctionInfo(SlotsDict):
    __slots__ = ('name', 'group', 'capabilities', 'description', 'path')

    def __init__(self, name: str, group: str, capabilities: list[str], description: str, path: str):
        self.name = name
        self.group = group
        self.capabilities = capabilities
        self.description = description
        self.path = path

class FunctionInfoEx(FunctionInfo):
    __slots__ = ('parameters', 'returns')

    def __init__(self,
                 parent: FunctionInfo,
                 parameters: 'IRElement | None',
                 returns: 'IRElement | None'):
        super().__init__(**parent.__dict__)

        self.parameters = parameters
        self.returns = returns

class DocString_TypeDescPair(SlotsDict):
    __slots__ = ('typ', 'description')
    typ: str
    description: str

    def __init__(self, typ: str, description: str):
        self.typ = typ
        self.description = description

class DocString(SlotsDict):
    __slots__ = ('description', 'params', 'returns', 'subpackage', 'copyright')
    description: str
    params: dict[str, DocString_TypeDescPair]
    returns: DocString_TypeDescPair | None
    subpackage: str | None
    copyright: tuple[int, str] | None

    def __init__(
        self,
        desc: str,
        params: dict[str, DocString_TypeDescPair],
        returns: DocString_TypeDescPair | None,
        subpackage: str | None,
        copyright: tuple[int, str] | None,
    ):
        self.description = desc
        self.params = params
        self.returns = returns
        self.subpackage = subpackage
        self.copyright = copyright

class ExtractedAPIFunction(SlotsDict):
    __slots__ = ('docstring', 'name', 'params', 'returns', 'body')
    docstring: DocString
    name: str
    params: dict[str, str]
    returns: str
    body: str

    def __init__(self, docstring: DocString, name: str, params: dict[str, str], returns: str, body: str):
        self.docstring = docstring
        self.name = name
        self.params = params
        self.returns = returns
        self.body = body

class IRElement(SlotsDict, ABC):
    __slots__ = ('description', 'required', 'type')

    def __init__(self, description: str, required: bool):
        self.description = description
        self.required = required

class IRValue(IRElement):
    __slots__ = ('default_value', 'type', 'nullable')

    def __init__(self, type, default_value, nullable: bool, **kwargs):
        self.type = type
        self.default_value = default_value
        self.nullable = nullable
        super().__init__(**kwargs)

class IRObject(IRElement):
    __slots__ = ('fields',)
    fields: dict[str, IRElement]

    def __init__(self, fields: dict[str, IRElement], **kwargs):
        self.fields = fields
        self.type = 'ObjectValue'
        super().__init__(**kwargs)

class IRArray(IRElement):
    __slots__ = ('value',)
    value: IRElement

    def __init__(self, value: IRElement, **kwargs):
        self.value = value
        self.type = 'ArrayValue'
        super().__init__(**kwargs)

def parse_code(code: str, nr: PHPNameResolution) -> PHPExpression:
    code = code.strip()
    while True:
        i, expr = parse_statement(code, nr)
        if expr is not None:
            return expr
        code = code[i:].strip()

def parse_statement(code: str, nr: PHPNameResolution) -> tuple[int, PHPExpression | None]:
    buf = []
    i = 0
    while True:
        c = code[i]
        if c.isalpha() or c == '_':
            buf.append(c)
            i += 1
        elif c.isspace():
            i += 1
            if len(buf) == 0:
                continue

            word = "".join(buf)
            buf = []

            if word == 'global':
                # just skip this statement; we're not interested in globals
                return i + code[i:].index(';') + 1, None
            elif word == 'return':
                iplus, expr = parse_expression(code[i:], nr)
                i += iplus

                return i + 1, expr
            else:
                raise ValueError(f"unknown keyword: {word}")
        elif c == ';':
            return i + 1, None
        elif code[i:i + 2] == '//':
            i += code[i:].index('\n')
        else:
            raise ValueError(f"unknown char: {c}")

def parse_expression(code: str, nr: PHPNameResolution) -> tuple[int, PHPExpression | None]:
    expr: PHPExpression | None = None

    buf: list[str] = []
    i = 0
    while True:
        if len(buf) == 0 and code[i:].startswith('$USER->id'):
            assert expr is None
            i += len('$USER->id')
            expr = PHPUserID()

        c = code[i]
        if c.isalpha() or c == '_':
            assert expr is None
            buf.append(c)
            i += 1
        elif c.isspace():
            i += 1
            if len(buf) == 0:
                continue

            word = "".join(buf)
            buf = []

            if word == 'new':
                iplus, expr = parse_constructor(code[i:], nr)
                i += iplus
            else:
                # just assume this is a constant
                assert expr is None
                expr = PHPConstant(word)
        elif c == '[':
            assert expr is None
            i += 1
            if len(buf) > 0:
                raise NotImplementedError("map access not implemented")

            iplus, expr = parse_array(code[i:], nr)
            i += iplus
        elif c in '\'"':
            assert len(buf) == 0
            assert expr is None

            iplus, expr = parse_string(code[i:])
            i += iplus
        elif c == '.':
            assert isinstance(expr, PHPString)
            i += 1
            iplus, after = parse_expression(code[i:], nr)
            i += iplus
            assert isinstance(after, PHPString)
            expr = PHPConcat(expr, after)
        elif code[i:i + 2] == '::':
            # remote value
            assert len(buf) > 0
            assert expr is None
            i += 2
            iplus = 1
            while 95 <= ord(code[i + iplus].lower()) <= 122: # until it hits non-word character
                iplus += 1
            is_func = code[i + iplus] == '('
            membername = code[i:i + iplus]
            classname = "".join(buf)
            i += iplus
            if is_func:
                assert code[i:i + 2] == '()'
                i += 2
                C: type[PHPClassMemberFunction]
                fp_import: str | None
                if membername == 'format':
                    C = PHPEnumFormat
                    fp_import = path.join(path.dirname(__file__), "lbplanner", "enums", f"{classname}.php")
                else:
                    C = PHPClassMemberFunction
                    fp_import = find_import(nr, classname)
                expr = C(classname, membername, fp_import).resolve()
                buf = []
            else:
                fp_import = path.join(path.dirname(__file__), "lbplanner", "enums", f"{classname}.php")
                expr = PHPEnumCase(classname, membername, fp_import).resolve()
                buf = []
        else:
            # unkown character? simply bail
            if len(buf) > 0:
                # assume we have a constant on our hands
                word = "".join(buf)
                assert expr is None
                expr = PHPConstant(word)
            return i, expr

def parse_constructor(code: str, nr: PHPNameResolution) -> tuple[int, PHPConstructor]:
    paramlist: list[PHPExpression] = []
    fnname, parenth, params = code.partition('(')
    assert fnname.replace('_', '').isalpha()
    assert parenth == '(' # if parenthesis not found, parenth is an empty string
    offset = len(fnname) + 1
    i = 0
    while True:
        iplus, expr = parse_expression(params[i:], nr)
        i += iplus

        if expr is not None:
            paramlist.append(expr)

        if params[i] == ',':
            i += 1
        elif params[i] == ')':
            return i + offset + 1, PHPConstructor(fnname, paramlist)
        else:
            raise ValueError(f"unknown char: {params[i]}")

def parse_array(code: str, nr: PHPNameResolution) -> tuple[int, PHPArray]:
    associative: bool | None = None
    keys: list[PHPString] = []
    vals: list[PHPExpression] = []

    i = 0
    while True:
        iplus, expr = parse_expression(code[i:], nr)
        i += iplus

        if code[i] == ',':
            i += 1
            assert expr is not None
            vals.append(expr)
            if associative is None:
                associative = False
        elif code[i:i + 2] == '=>':
            i += 2
            assert isinstance(expr, PHPString)
            keys.append(expr)
            associative = True
        elif code[i] == ']':
            if expr is not None:
                vals.append(expr)
            if associative is True:
                assert len(keys) == len(vals)
            return i + 1, PHPArray(vals, keys if associative else None)
        else:
            raise ValueError(f"unknown char: {code[i]}")

def parse_string(code: str) -> tuple[int, PHPStringLiteral]:
    quotetype = code[0]
    assert quotetype in '\'"'
    simple = quotetype == '\''
    if not simple:
        raise NotImplementedError() # TODO
    result: list[str] = []
    i = 1
    while True:
        c = code[i]
        i += 1
        if c == quotetype:
            return i, PHPStringLiteral("".join(result))
        elif c == '\\':
            if code[i] == quotetype:
                result.append(quotetype)
            elif code[i] == '\\':
                result.append('\\')
            elif simple:
                result.append('\\')
                result.append(code[i])
            elif code[i] == 'n':
                result.append('\n')
            elif code[i] == 'r':
                result.append('\r')
            else:
                raise NotImplementedError(f"can't escape \"{code[i]}\" in double-quoted string")
            i += 1
        else:
            if simple:
                result.append(c)

def extract_function_info(file_content: str) -> list[FunctionInfo]:
    function_infos = []

    # Removing line comments, PHP tags, and definitions
    clean_content = re.sub(r"//.*|<\?php|defined\(.*\)\s*\|\|\s*die\(\);", "", file_content)

    # Splitting the content based on function definition blocks
    # https://regex101.com/r/qyzYks
    functions = re.findall(r"'(local_lbplanner_(\w+?)_(\w+))' => \[(.*?)\],", clean_content, re.DOTALL)

    # to make sure we never accidentally duplicate descriptions
    existing_descriptions = []

    for function in functions:
        func_dict = {}

        # Extracting function name and group
        func_dict["name"] = function[2]
        func_dict["group"] = function[1]

        # Extracting and adjusting capabilities
        capabilities = re.search(r"'capabilities' => '(.*?:.*?)'", function[3])
        if capabilities is None:
            # check if call needs no capabilities
            capabilities = re.search(r"'capabilities' => ''", function[3])
            func_dict["capabilities"] = [] if capabilities else None
        else:
            func_dict["capabilities"] = [cap.strip() for cap in capabilities.group(1).split(',') if len(cap) > 0]

        # Extracting description
        description = re.search(r"'description' => '([^\n]*)'", function[3])
        func_dict["description"] = description.group(1).replace('\\\'', '\'') if description else None

        # Extracting and adjusting path
        classpath = re.search(r"'classpath' => 'local/(.*?)'", function[3])
        func_dict["path"] = classpath.group(1) if classpath else None

        # Only adding to the list if all information is present
        if all(value is not None for value in func_dict.values()):
            finfo = FunctionInfo(**func_dict)
            function_infos.append(finfo)

            if finfo.description in existing_descriptions:
                warn("duplicated API function description", finfo.description)
            else:
                existing_descriptions.append(finfo.description)
        else:
            warn("Could not gather all info for API function", func_dict["name"], func_dict)

    if len(function_infos) == 0:
        warn("Couldn't find any functions!")

    # double-checking using the services list below
    services_function_block = re.search(r"\$services = \[.*?'functions' => \[(['a-z_,\s]+)\]", clean_content, re.DOTALL)
    if services_function_block is None:
        warn("Couldn't find $services")
    else:
        services_functions = re.findall(r"'local_lbplanner_([a-z]+)_([a-z_]+)'", services_function_block[1])

        function_infos_copy = function_infos.copy()
        for function in services_functions:
            # Extracting function name and group
            func_name = function[1]
            func_group = function[0]

            found = False
            for functioninfo in function_infos_copy:
                if functioninfo.name == func_name and functioninfo.group == func_group:
                    function_infos_copy.remove(functioninfo)
                    found = True
                    break

            if not found:
                warn("Couldn't find service function in $functions", f"{func_group}_{func_name}")

        for functioninfo in function_infos_copy:
            # The ones left here are not in services_function.
            warn("Couldn't find service function in $services", f"{functioninfo.group}_{functioninfo.name}")

    # double-checking using existing files
    function_infos_copy = function_infos.copy()
    searchdir = './lbplanner/services'
    for subdir in listdir(searchdir):
        dirpath = path.join(searchdir, subdir)
        if not path.isdir(dirpath):
            warn('found file in services folder', subdir)
            continue

        for filename in listdir('lbplanner/services/' + subdir):
            if path.isdir(filename):
                warn('found directory in folder', filename, dirpath)
                continue
            if not filename.endswith('.php'):
                warn('found non-php file in folder', filename, dirpath)
                continue

            for functioninfo in function_infos_copy:
                if functioninfo.name == filename[:-4] and functioninfo.group == subdir:
                    function_infos_copy.remove(functioninfo)
                    found = True
                    break

            if not found:
                warn("Couldn't find service function in $function", f"{func_group}_{func_name}")

    for functioninfo in function_infos_copy:
        # The ones left here are not in the dirs.
        warn("Couldn't find file in services folder", f"{functioninfo.group}/{functioninfo.name}.php")

    return function_infos


def extract_api_functions(php_code: str, name: str) -> tuple[ExtractedAPIFunction | None, ExtractedAPIFunction | None, ExtractedAPIFunction | None]:
    # Regular expression to match the function names and bodies
    # https://regex101.com/r/9GtIMA
    pattern = re.compile(
        r"(?P<docstring>    /\*\*\n(?:     \*[^\n]*\n)*?     \*/)\s*public static function (?P<name>\w+(?:_returns|_parameters|))\(\s*(?P<params>(?:\??(?:int|string|bool) \$[a-z]*(?:,\s+)?)*)\)(?:: (?P<returns>[a-z_]+))? (?P<body>{.+?^    }$)",
        re.DOTALL | re.MULTILINE
    )

    # Find all matches in the PHP code
    matches: list[tuple[str, str]] = pattern.findall(php_code)

    parameters_function = None
    returns_function = None
    main_function = None

    for match in matches:
        # Extract function name
        if len(match) == 4:
            func_docstring, func_name, func_params, func_body = match
            func_returns = None
        elif len(match) == 5:
            func_docstring, func_name, func_params, func_returns, func_body = match
        else:
            raise Exception("unreachable")

        function_packed = ExtractedAPIFunction(
            parse_docstring(func_docstring),
            func_name,
            parse_php_function_parameters(func_params),
            func_returns,
            func_body
        )

        if func_name.endswith("_parameters"):
            parameters_function = function_packed
        elif func_name.endswith("_returns"):
            returns_function = function_packed
        else:
            main_function = function_packed

    if parameters_function is None:
        warn("Couldn't find parameters function", name, php_code)
    if returns_function is None:
        warn("Couldn't find returns function", name, php_code)
    if main_function is None:
        warn("Couldn't find main function", name, php_code)

    return parameters_function, main_function, returns_function

def extract_main_api_docstring(inpot: str) -> DocString:
    return parse_docstring(inpot[inpot.find('/**'):inpot.find('*/')])

def parse_docstring(inpot: str) -> DocString:
    desc_a = []
    params: dict[str, DocString_TypeDescPair] = {}
    returns: DocString_TypeDescPair | None = None
    subpackage: str | None = None
    copyright: tuple[int, str] | None = None
    isdesc = True
    for line in inpot.splitlines():
        strippedline = line[line.find('*') + 1:].strip()
        if strippedline in ('*', ' ', ''):
            continue # empty line, ignore
        elif strippedline == '/':
            break # last line, ignore
        elif strippedline.startswith('NOTE:'):
            isdesc = False
            continue # internal notes, ignore
        elif strippedline.startswith('@'):
            isdesc = False
            splitline = strippedline.split(' ')
            match splitline[0]:
                case '@param':
                    if splitline[2] in params:
                        warn("specified @param twice in docstring", splitline[2])
                    params[splitline[2]] = DocString_TypeDescPair(splitline[1], " ".join(splitline[3:]))
                case '@return':
                    if returns is not None:
                        warn("specified @returns twice in docstring")
                    returns = DocString_TypeDescPair(splitline[1], " ".join(splitline[2:]))
                case '@package':
                    if splitline[1] != "local_lbplanner":
                        warn("found @package with invalid value instead of local_lbplanner", splitline[1])
                case '@subpackage':
                    subpackage = " ".join(splitline[1:])
                case '@copyright':
                    copyright = int(splitline[1]), " ".join(splitline[2:])
                case '@throws' | '@see' | '@link' | '@license':
                    pass
                case unknown:
                    warn("unknown @-rule", unknown, line)
        elif isdesc:
            desc_a.append(strippedline)

    desc = " ".join(desc_a)

    # remove trailing period
    if desc.endswith('.'):
        desc = desc[:-1]

    return DocString(desc, params, returns, subpackage, copyright)

def parse_php_function_parameters(inpot: str) -> dict[str, str]:
    """ "int $a, string $b" → {"a": "int", "b": "string"} """
    # https://regex101.com/r/zfWGKi
    matches = re.findall(r"(\??[a-z]+)\s+\$([a-z_]+)", inpot)
    out = {}
    for match in matches:
        out[match[1]] = match[0]

    return out

def find_import(nr: PHPNameResolution, symbol: str) -> str | None:

    def makepath(p: str, symbol: str):
        return path.join(path.dirname(__file__), "lbplanner", p, f"{symbol}.php")

    namespaces = { # it's technically possible to import from outside /classes/
        "helpers": "classes/helpers",
        "enums": "classes/enums",
        "polyfill": "classes/polyfill",
        "model": "classes/model",
    }
    fp_l: list[str] = []
    for use in nr.imports:
        im_symbol = use.split('\\')[-1].replace(';', '')
        found = False
        if im_symbol.startswith('{'):
            for subsymbol in im_symbol[1:-1].split(','):
                if subsymbol.strip() == symbol:
                    found = True
                    break
        else:
            found = symbol == im_symbol
        if not found:
            continue
        for namespace, p in namespaces.items():
            if use.startswith(namespace):
                fp_l.append(makepath(p, symbol))

    if len(fp_l) == 0 and nr.namespace is not None:
        fallback = makepath(namespaces[nr.namespace], symbol)

        if path.exists(fallback):
            fp_l.append(fallback)

    if len(fp_l) > 1:
        warn("found potential import collision", f"{symbol} in [{nr}]")
        return None
    elif len(fp_l) == 0:
        warn("couldn't find symbol", f"{symbol} in [{nr}]")
        return None
    else:
        return fp_l[0]

def extract_imports(input_str: str) -> PHPNameResolution:
    useprefix = "use local_lbplanner\\"
    nsprefix = "namespace local_lbplanner\\"
    imports = []
    namespace = None

    for line in input_str.splitlines(False):
        if line.startswith(useprefix):
            imports.append(line.removeprefix(useprefix))
        if line.startswith(nsprefix):
            assert namespace is None
            namespace = line.removeprefix(nsprefix).removesuffix(';')

    return PHPNameResolution(namespace, imports)

def parse_function(input_text: str, nr: PHPNameResolution) -> IRElement | None:
    ss = input_text.index('{')
    se = input_text.rindex('}')
    func_body = input_text[ss + 1:se]

    expr = parse_code(func_body, nr)

    if isinstance(expr, PHPConstant) and expr.name == 'null':
        return None
    elif not isinstance(expr, PHPConstructor):
        warn("non-constructor at top level", expr)
        return None

    topelement = expr.toIR()
    if isinstance(topelement, IRObject) and len(topelement.fields) == 0:
        return None
    else:
        return topelement


def main() -> None:
    global CURRENT_SERVICE
    with open("lbplanner/db/services.php", "r") as file:
        content = file.read()

    infos = extract_function_info(content)

    complete_info = []

    for i, info in enumerate(infos):

        CURRENT_SERVICE = info.name

        with open(info.path, "r") as func_file:
            func_content = func_file.read()

        imports = extract_imports(func_content)
        params_func, main_func, returns_func = extract_api_functions(func_content, info.path)
        main_docstring = extract_main_api_docstring(func_content)

        if returns_func is None or params_func is None:
            continue

        returns = parse_function(returns_func.body, imports)

        params = parse_function(params_func.body, imports)

        complete_info.append(FunctionInfoEx(info, params, returns))

        if main_func is not None:
            # checking function descriptions
            if main_func.docstring.description != info.description or main_docstring.description != info.description:
                warn(
                    "non-matching API function descriptions",
                    f"func docstring:      {main_func.docstring.description}",
                    f"class docstring:     {main_docstring.description}",
                    f"service description: {info.description}",
                )

            # checking parameters
            all_param_names = set()
            params_moodleset: dict[str, tuple[str, bool]] = {}
            if isinstance(params, IRObject):
                for name, param in params.fields.items():
                    if isinstance(param, IRValue):
                        params_moodleset[name] = param.type, param.nullable
                        all_param_names.add(name)
                    else:
                        warn("parameters' IRObject contains non-IRValue", param, params)
            elif params is not None:
                warn("parameters function does not return IRObject", params)

            params_docstringset: dict[str, tuple[str, bool]] = {}
            for name, docpair in main_func.docstring.params.items():
                name = name[1:] # removing dollar sign
                params_docstringset[name] = convert_php_type_to_normal_type(docpair.typ)
                all_param_names.add(name)

            params_phpset: dict[str, tuple[str, bool]] = {}
            for name, typ in main_func.params.items():
                params_phpset[name] = convert_php_type_to_normal_type(typ)

            for name in all_param_names:
                if not (
                        name in params_moodleset.keys()
                    and name in params_docstringset.keys()
                    and name in params_phpset.keys()
                ):
                    warn(
                        "API call parameter not found in all parameter lists",
                        f"moodle: {params_moodleset}",
                        f"docstring: {params_docstringset}",
                        f"php: {params_phpset}",
                    )
                elif not (params_moodleset[name] == params_docstringset[name] == params_phpset[name]):
                    warn(
                        "API call parameter not the same type in all parameter lists",
                        name,
                        f"moodle:    {params_moodleset[name]}",
                        f"docstring: {params_docstringset[name]}",
                        f"php:       {params_phpset[name]}",
                    )

        # checking copyright
        if main_docstring.copyright is None:
            warn("missing copyright notice")
        else:
            with Popen(["git", "log", "-1", '--pretty=format:%as', info.path], stdout=PIPE) as p:
                lastmodificationyear = int(p.communicate()[0].decode('utf-8').split('-')[0])
            if main_docstring.copyright[0] != lastmodificationyear:
                warn(
                    "incorrect copyright year",
                    f"expected: {lastmodificationyear}",
                    f"got:      {main_docstring.copyright[0]}"
                )
            if main_docstring.copyright[1] != "necodeIT":
                warn(
                    "incorrect copyright name",
                    "expected: necodeIT",
                    f"got:     {main_docstring.copyright[1]}"
                )

        # checking subpackage
        expected_subpackage = 'services_' + path.basename(path.dirname(info.path))
        if expected_subpackage != main_docstring.subpackage:
            warn(
                "incorrect subpackage",
                f"expected: {expected_subpackage}",
                f"got:      {main_docstring.subpackage}"
            )

    CURRENT_SERVICE = None

    data = json.dumps(complete_info, default=lambda x: x.__dict__)

    if sys.argv[1] == "-":
        print(data)
    elif sys.argv[1] == "/dev/null":
        pass
    else:
        declaration = f"const funcs = {data}"

        script: str
        with open(f"{sys.argv[1]}/script.js", "r") as f:
            script = f.read()
            lines = script.splitlines()
            for i in range(len(lines)):
                if lines[i].startswith('const funcs = '):
                    lines[i] = declaration
            script = "\n".join(lines)

        with open(f"{sys.argv[1]}/script.js", "w") as f:
            f.write(script)

    if len(WARNCOUNT) > 0:
        total_warns = sum(count for count in WARNCOUNT.values())
        print(f"printed \033[33m{total_warns}\033[0m warnings in total (\033[33m{total_warns / len(infos):.2f}\033[0m per \033[36mservice\033[0m)", file=sys.stderr)
        for msg, count in WARNCOUNT.items():
            print(f"\033[33m{count:02d}\033[0mx \033[31m{msg}\033[0m")
        sys.exit(1)


if __name__ == "__main__":
    main()
