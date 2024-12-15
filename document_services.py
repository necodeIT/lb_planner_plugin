import json
import re
import sys
from os import path
from abc import ABC, abstractmethod
import traceback as tb

from typing import Any, Iterable

HAS_WARNED = False
CURRENT_SERVICE: str | None = None

def warn(msg: str, *context: Any):
    """Prints a warning message to the console and sets the global HAS_WARNED variable to True.

    :param str msg: The warning message to print.
    """
    global HAS_WARNED
    WARN = "\033[43m\033[30mWARN:\033[0m "
    WARN_TAB = "    \033[43m\033[33m|\033[0m "

    HAS_WARNED = True

    stack = tb.extract_stack()
    stack_str = " -> ".join([f"\033[34m{frame.name}\033[0m" for frame in stack if frame != stack[-1]])

    service_msg: str
    if CURRENT_SERVICE is None:
        service_msg = "outside any service"
    else:
        service_msg = f"in service \033[36m{CURRENT_SERVICE}\033[0m"

    print(
        f"{WARN}\033[31m{msg}\033[0m {service_msg} ({stack_str})",
        f"\n{WARN_TAB}  " if len(context) > 0 else "",
        *[f"\033[2m{c}\033[0m".replace('\n', '\n\033[0m' + WARN_TAB + "  \033[2m") for c in context],
        file=sys.stderr,
        sep=""
    )

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
        warn(f"found weird value for nullable: {inpot}")
        return None

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
                warn(f"Couldn't find {self} inside {self.fp}")
                return PHPConstant('null')
            elif len(meth_matches) > 1:
                raise Exception(f"Found multiple definitions for {self} inside {self.fp}")
            else:
                imports = extract_imports(new_file_content)
                result = parse_function(meth_matches[0], imports)

                return result

    def __str__(self) -> str:
        return f"{self.classname}::{self.funcname}()"

class PHPEnumFormat(PHPClassMemberFunction, PHPString):
    def resolve(self) -> PHPString:
        # https://regex101.com/r/p5FzCh
        casepattern = r"const (\w+) = (\d+|true|false|(['\"]).*?\3)"

        fullbody_pattern = f"class {self.classname} extends Enum {{.*?}}"

        fp = f"lbplanner/classes/enums/{self.classname}.php"
        if not path.exists(fp):
            warn(f"Couldn't find enum file {fp}")
            return PHPStringLiteral("")
        with open(fp, "r") as f:
            matches: list[str] = re.findall(fullbody_pattern, f.read(), re.DOTALL)
            if len(matches) == 1:
                body = matches[0]
            else:
                warn(f"couldn't parse enum {self.classname}", matches)

        cases = {}
        matches = re.findall(casepattern, body)
        for match in matches:
            # capitalizing first letter, if exists
            name = "".join([match[0][0].upper(), match[0][1:].lower()])
            cases[name] = match[1].replace("'", '"')

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
        slots = tuple()

        for cls in self.__class__.__mro__:
            if cls != SlotsDict and issubclass(cls, SlotsDict):
                slots = cls.__slots__ + slots
        return {name: self.__getattribute__(name) for name in slots}

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
                 parameters: PHPExpression,
                 returns: PHPExpression):
        super().__init__(**parent.__dict__)

        self.parameters = parameters
        self.returns = returns

def parse_code(code: str, imports: list[str]):
    code = code.strip()
    while len(code) > 0:
        i, expr = parse_statement(code, imports)
        if expr is not None:
            break
        code = code[i:].strip()

    return expr

def parse_statement(code: str, imports: list[str]) -> tuple[int, PHPExpression | None]:
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
                iplus, expr = parse_expression(code[i:], imports)
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

def parse_expression(code: str, imports: list[str]) -> tuple[int, PHPExpression | None]:
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
                iplus, expr = parse_constructor(code[i:], imports)
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

            iplus, expr = parse_array(code[i:])
            i += iplus
        elif c in '\'"':
            assert len(buf) == 0
            assert expr is None

            iplus, expr = parse_string(code[i:])
            i += iplus
        elif c == '.':
            assert isinstance(expr, PHPString)
            i += 1
            iplus, after = parse_expression(code[i:], imports)
            i += iplus
            assert isinstance(after, PHPString)
            expr = PHPConcat(expr, after)
        elif code[i:i + 2] == '::':
            # remote value
            assert len(buf) > 0
            assert expr is None
            i += 2
            iplus = code[i:].index('(')
            funcname = code[i:i + iplus]
            classname = "".join(buf)
            i += iplus
            assert code[i:i + 2] == '()'
            i += 2
            C = PHPEnumFormat if funcname == 'format' else PHPClassMemberFunction
            fp_import = find_import(imports, classname)
            expr = C(classname, funcname, fp_import)
            buf = []
        else:
            # unkown character? simply bail
            if len(buf) > 0:
                # assume we have a constant on our hands
                word = "".join(buf)
                assert expr is None
                expr = PHPConstant(word)
            return i, expr

def parse_constructor(code: str, imports: list[str]) -> tuple[int, PHPConstructor]:
    paramlist: list[PHPExpression] = []
    fnname, parenth, params = code.partition('(')
    assert fnname.replace('_', '').isalpha()
    assert parenth == '(' # if parenthesis not found, parenth is an empty string
    offset = len(fnname) + 1
    i = 0
    while True:
        iplus, expr = parse_expression(params[i:], imports)
        i += iplus

        if expr is not None:
            paramlist.append(expr)

        if params[i] == ',':
            i += 1
        elif params[i] == ')':
            return i + offset + 1, PHPConstructor(fnname, paramlist)
        else:
            raise ValueError(f"unknown char: {params[i]}")

def parse_array(code: str) -> tuple[int, PHPArray]:
    associative: bool | None = None
    keys: list[PHPString] = []
    vals: list[PHPExpression] = []

    i = 0
    while True:
        iplus, expr = parse_expression(code[i:], imports)
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
            i += 1
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
        else:
            if simple:
                result.append(c)

def extract_function_info(file_content: str) -> list[FunctionInfo]:
    function_info = []

    # Removing comments, PHP tags, and definitions
    clean_content = re.sub(r"//.*|<\?php|defined\(.*\)\s*\|\|\s*die\(\);", "", file_content)

    # Splitting the content based on function definition blocks
    # https://regex101.com/r/qyzYks
    functions = re.findall(r"'(local_lbplanner_(\w+?)_(\w+))' => \[(.*?)\],", clean_content, re.DOTALL)

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
        description = re.search(r"'description' => '(.*?)'", function[3])
        func_dict["description"] = description.group(1) if description else None

        # Extracting and adjusting path
        classpath = re.search(r"'classpath' => 'local/(.*?)'", function[3])
        func_dict["path"] = classpath.group(1) if classpath else None

        # Only adding to the list if all information is present
        if all(value is not None for value in func_dict.values()):
            function_info.append(FunctionInfo(**func_dict))
        else:
            warn(f"Could not gather all info for {func_dict["name"]}", func_dict)

    if len(function_info) == 0:
        warn("Couldn't find any functions!")

    return function_info


def extract_php_functions(php_code: str, name: str) -> tuple[str | None, str | None]:
    # Regular expression to match the function names and bodies
    # https://regex101.com/r/9GtIMA
    pattern = re.compile(r"(public static function (\w+_(?:returns|parameters))\W[^{}]*?{[^{}]+?})", re.DOTALL)

    # Find all matches in the PHP code
    matches: list[tuple[str, str]] = pattern.findall(php_code)

    parameters_function = None
    returns_function = None

    for match in matches:
        # Extract function name
        function_name = match[1]

        if function_name.endswith("_parameters"):
            parameters_function = match[0]
        elif function_name.endswith("_returns"):
            returns_function = match[0]

    if parameters_function is None:
        warn(f"Couldn't find parameters function in {name}")
    if returns_function is None:
        warn(f"Couldn't find returns function in {name}")

    return parameters_function, returns_function

def find_import(uses: list[str], symbol: str) -> str | None:

    namespaces = { # it's technically possible to import from outside /classes/
        "helpers": "classes/helpers",
        "enums": "classes/enums",
        "polyfill": "classes/polyfill",
        "model": "classes/model",
    }
    fp_l: list[str] = []
    for use in uses:
        im_symbol = use.split('\\')[-1].replace(';', '')
        found = False
        if im_symbol.startswith('{'):
            for subsymbol in im_symbol.split(','):
                if subsymbol.strip() == symbol:
                    found = True
                    break
        else:
            found = symbol == im_symbol
        if not found:
            continue
        for namespace, p in namespaces.items():
            if use.startswith(namespace):
                fp_l.append(path.join(path.dirname(__file__), "lbplanner", p, f"{symbol}.php"))

    if len(fp_l) > 1:
        warn(f"found potential import collision for {symbol}", uses)
        return None
    elif len(fp_l) == 0:
        warn(f"Couldn't find symbol: {symbol}", uses)
        return None
    else:
        return fp_l[0]

def extract_imports(input_str: str) -> list[str]:
    prefix = "use local_lbplanner\\"
    imports = []

    for line in input_str.splitlines(False):
        if line.startswith(prefix):
            imports.append(line.removeprefix(prefix))

    return imports

def parse_function(input_text: str, imports: list[str]) -> PHPExpression:
    ss = input_text.index('{')
    se = input_text.rindex('}')
    func_body = input_text[ss + 1:se]

    return parse_code(func_body, imports)


if __name__ == "__main__":
    with open("lbplanner/db/services.php", "r") as file:
        content = file.read()

    infos = extract_function_info(content)

    complete_info = []

    for i, info in enumerate(infos):

        CURRENT_SERVICE = info.name

        with open(info.path, "r") as func_file:
            func_content = func_file.read()
            imports = extract_imports(func_content)
            params_func, returns_func = extract_php_functions(func_content, info.path)

            if returns_func is None or params_func is None:
                continue

            returns = parse_function(returns_func, imports)

            params = parse_function(params_func, imports)

            print(info.name)
            print(params)
            print(returns)

            complete_info.append(FunctionInfoEx(info, params, returns))

    CURRENT_SERVICE = None

    # TODO: intermediary step

    data = json.dumps(complete_info, default=lambda x: x.__dict__)

    if sys.argv[1] == "-":
        print(data)
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

    if HAS_WARNED:
        sys.exit(1)
