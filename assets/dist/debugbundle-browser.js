(() => {
  var __defProp = Object.defineProperty;
  var __defNormalProp = (obj, key, value) => key in obj ? __defProp(obj, key, { enumerable: true, configurable: true, writable: true, value }) : obj[key] = value;
  var __export = (target, all) => {
    for (var name in all)
      __defProp(target, name, { get: all[name], enumerable: true });
  };
  var __publicField = (obj, key, value) => __defNormalProp(obj, typeof key !== "symbol" ? key + "" : key, value);

  // node_modules/.pnpm/@debugbundle+redaction@0.1.8/node_modules/@debugbundle/redaction/dist/index.js
  var DEFAULT_SENSITIVE_KEYS = [
    "password",
    "secret",
    "token",
    "api_key",
    "apikey",
    "access_token",
    "refresh_token",
    "private_key",
    "authorization",
    "bearer",
    "cookie",
    "session_id",
    "passwd",
    "ssn",
    "credit_card",
    "card_number",
    "cvv",
    "cvc",
    "pin",
    "expiry",
    "phone",
    "otp",
    "verification_code"
  ];
  function canonicalizeSensitiveKey(value) {
    return value.replace(/[^a-z0-9]+/g, "");
  }
  function splitKeyIntoSegments(key) {
    return key.trim().replace(/([a-z0-9])([A-Z])/g, "$1_$2").toLowerCase().split(/[^a-z0-9]+/).filter((segment) => segment.length > 0);
  }
  function buildKeyCandidates(key) {
    const segments = splitKeyIntoSegments(key);
    const candidates = /* @__PURE__ */ new Set();
    for (let start = 0; start < segments.length; start += 1) {
      let combined = "";
      for (let end = start; end < segments.length; end += 1) {
        combined += segments[end];
        candidates.add(combined);
      }
    }
    return [...candidates];
  }
  function isSensitiveKey(key, sensitiveKeys) {
    const normalized = key.trim().toLowerCase();
    if (sensitiveKeys.includes(normalized)) {
      return true;
    }
    const canonicalSensitiveKeys = new Set(sensitiveKeys.map(canonicalizeSensitiveKey));
    const candidates = buildKeyCandidates(key);
    return candidates.some((candidate) => canonicalSensitiveKeys.has(candidate));
  }
  function redactInternal(value, path, sensitiveKeys, replacement, touchedPaths, seen) {
    if (Array.isArray(value)) {
      if (seen.has(value)) {
        return "[Circular]";
      }
      seen.add(value);
      const redactedArray = value.map((entry, index) => redactInternal(entry, path.length === 0 ? `[${index}]` : `${path}[${index}]`, sensitiveKeys, replacement, touchedPaths, seen));
      seen.delete(value);
      return redactedArray;
    }
    if (value === null || typeof value !== "object") {
      return value;
    }
    if (seen.has(value)) {
      return "[Circular]";
    }
    seen.add(value);
    const output = {};
    for (const [key, nestedValue] of Object.entries(value)) {
      const nextPath = path.length === 0 ? key : `${path}.${key}`;
      if (isSensitiveKey(key, sensitiveKeys)) {
        output[key] = replacement;
        touchedPaths.push(nextPath);
        continue;
      }
      output[key] = redactInternal(nestedValue, nextPath, sensitiveKeys, replacement, touchedPaths, seen);
    }
    seen.delete(value);
    return output;
  }
  function redact(payload, options) {
    var _a, _b, _c;
    const sensitiveKeys = (_b = (_a = options == null ? void 0 : options.sensitiveKeys) == null ? void 0 : _a.map((key) => key.trim().toLowerCase())) != null ? _b : [...DEFAULT_SENSITIVE_KEYS];
    const replacement = (_c = options == null ? void 0 : options.replacement) != null ? _c : "[REDACTED]";
    const touchedPaths = [];
    const redacted = redactInternal(payload, "", sensitiveKeys, replacement, touchedPaths, /* @__PURE__ */ new WeakSet());
    return {
      redacted,
      redacted_fields: touchedPaths
    };
  }

  // node_modules/.pnpm/zod@3.25.76/node_modules/zod/v3/external.js
  var external_exports = {};
  __export(external_exports, {
    BRAND: () => BRAND,
    DIRTY: () => DIRTY,
    EMPTY_PATH: () => EMPTY_PATH,
    INVALID: () => INVALID,
    NEVER: () => NEVER,
    OK: () => OK,
    ParseStatus: () => ParseStatus,
    Schema: () => ZodType,
    ZodAny: () => ZodAny,
    ZodArray: () => ZodArray,
    ZodBigInt: () => ZodBigInt,
    ZodBoolean: () => ZodBoolean,
    ZodBranded: () => ZodBranded,
    ZodCatch: () => ZodCatch,
    ZodDate: () => ZodDate,
    ZodDefault: () => ZodDefault,
    ZodDiscriminatedUnion: () => ZodDiscriminatedUnion,
    ZodEffects: () => ZodEffects,
    ZodEnum: () => ZodEnum,
    ZodError: () => ZodError,
    ZodFirstPartyTypeKind: () => ZodFirstPartyTypeKind,
    ZodFunction: () => ZodFunction,
    ZodIntersection: () => ZodIntersection,
    ZodIssueCode: () => ZodIssueCode,
    ZodLazy: () => ZodLazy,
    ZodLiteral: () => ZodLiteral,
    ZodMap: () => ZodMap,
    ZodNaN: () => ZodNaN,
    ZodNativeEnum: () => ZodNativeEnum,
    ZodNever: () => ZodNever,
    ZodNull: () => ZodNull,
    ZodNullable: () => ZodNullable,
    ZodNumber: () => ZodNumber,
    ZodObject: () => ZodObject,
    ZodOptional: () => ZodOptional,
    ZodParsedType: () => ZodParsedType,
    ZodPipeline: () => ZodPipeline,
    ZodPromise: () => ZodPromise,
    ZodReadonly: () => ZodReadonly,
    ZodRecord: () => ZodRecord,
    ZodSchema: () => ZodType,
    ZodSet: () => ZodSet,
    ZodString: () => ZodString,
    ZodSymbol: () => ZodSymbol,
    ZodTransformer: () => ZodEffects,
    ZodTuple: () => ZodTuple,
    ZodType: () => ZodType,
    ZodUndefined: () => ZodUndefined,
    ZodUnion: () => ZodUnion,
    ZodUnknown: () => ZodUnknown,
    ZodVoid: () => ZodVoid,
    addIssueToContext: () => addIssueToContext,
    any: () => anyType,
    array: () => arrayType,
    bigint: () => bigIntType,
    boolean: () => booleanType,
    coerce: () => coerce,
    custom: () => custom,
    date: () => dateType,
    datetimeRegex: () => datetimeRegex,
    defaultErrorMap: () => en_default,
    discriminatedUnion: () => discriminatedUnionType,
    effect: () => effectsType,
    enum: () => enumType,
    function: () => functionType,
    getErrorMap: () => getErrorMap,
    getParsedType: () => getParsedType,
    instanceof: () => instanceOfType,
    intersection: () => intersectionType,
    isAborted: () => isAborted,
    isAsync: () => isAsync,
    isDirty: () => isDirty,
    isValid: () => isValid,
    late: () => late,
    lazy: () => lazyType,
    literal: () => literalType,
    makeIssue: () => makeIssue,
    map: () => mapType,
    nan: () => nanType,
    nativeEnum: () => nativeEnumType,
    never: () => neverType,
    null: () => nullType,
    nullable: () => nullableType,
    number: () => numberType,
    object: () => objectType,
    objectUtil: () => objectUtil,
    oboolean: () => oboolean,
    onumber: () => onumber,
    optional: () => optionalType,
    ostring: () => ostring,
    pipeline: () => pipelineType,
    preprocess: () => preprocessType,
    promise: () => promiseType,
    quotelessJson: () => quotelessJson,
    record: () => recordType,
    set: () => setType,
    setErrorMap: () => setErrorMap,
    strictObject: () => strictObjectType,
    string: () => stringType,
    symbol: () => symbolType,
    transformer: () => effectsType,
    tuple: () => tupleType,
    undefined: () => undefinedType,
    union: () => unionType,
    unknown: () => unknownType,
    util: () => util,
    void: () => voidType
  });

  // node_modules/.pnpm/zod@3.25.76/node_modules/zod/v3/helpers/util.js
  var util;
  (function(util2) {
    util2.assertEqual = (_) => {
    };
    function assertIs(_arg) {
    }
    util2.assertIs = assertIs;
    function assertNever(_x) {
      throw new Error();
    }
    util2.assertNever = assertNever;
    util2.arrayToEnum = (items) => {
      const obj = {};
      for (const item of items) {
        obj[item] = item;
      }
      return obj;
    };
    util2.getValidEnumValues = (obj) => {
      const validKeys = util2.objectKeys(obj).filter((k) => typeof obj[obj[k]] !== "number");
      const filtered = {};
      for (const k of validKeys) {
        filtered[k] = obj[k];
      }
      return util2.objectValues(filtered);
    };
    util2.objectValues = (obj) => {
      return util2.objectKeys(obj).map(function(e) {
        return obj[e];
      });
    };
    util2.objectKeys = typeof Object.keys === "function" ? (obj) => Object.keys(obj) : (object) => {
      const keys = [];
      for (const key in object) {
        if (Object.prototype.hasOwnProperty.call(object, key)) {
          keys.push(key);
        }
      }
      return keys;
    };
    util2.find = (arr, checker) => {
      for (const item of arr) {
        if (checker(item))
          return item;
      }
      return void 0;
    };
    util2.isInteger = typeof Number.isInteger === "function" ? (val) => Number.isInteger(val) : (val) => typeof val === "number" && Number.isFinite(val) && Math.floor(val) === val;
    function joinValues(array, separator = " | ") {
      return array.map((val) => typeof val === "string" ? `'${val}'` : val).join(separator);
    }
    util2.joinValues = joinValues;
    util2.jsonStringifyReplacer = (_, value) => {
      if (typeof value === "bigint") {
        return value.toString();
      }
      return value;
    };
  })(util || (util = {}));
  var objectUtil;
  (function(objectUtil2) {
    objectUtil2.mergeShapes = (first, second) => {
      return {
        ...first,
        ...second
        // second overwrites first
      };
    };
  })(objectUtil || (objectUtil = {}));
  var ZodParsedType = util.arrayToEnum([
    "string",
    "nan",
    "number",
    "integer",
    "float",
    "boolean",
    "date",
    "bigint",
    "symbol",
    "function",
    "undefined",
    "null",
    "array",
    "object",
    "unknown",
    "promise",
    "void",
    "never",
    "map",
    "set"
  ]);
  var getParsedType = (data) => {
    const t = typeof data;
    switch (t) {
      case "undefined":
        return ZodParsedType.undefined;
      case "string":
        return ZodParsedType.string;
      case "number":
        return Number.isNaN(data) ? ZodParsedType.nan : ZodParsedType.number;
      case "boolean":
        return ZodParsedType.boolean;
      case "function":
        return ZodParsedType.function;
      case "bigint":
        return ZodParsedType.bigint;
      case "symbol":
        return ZodParsedType.symbol;
      case "object":
        if (Array.isArray(data)) {
          return ZodParsedType.array;
        }
        if (data === null) {
          return ZodParsedType.null;
        }
        if (data.then && typeof data.then === "function" && data.catch && typeof data.catch === "function") {
          return ZodParsedType.promise;
        }
        if (typeof Map !== "undefined" && data instanceof Map) {
          return ZodParsedType.map;
        }
        if (typeof Set !== "undefined" && data instanceof Set) {
          return ZodParsedType.set;
        }
        if (typeof Date !== "undefined" && data instanceof Date) {
          return ZodParsedType.date;
        }
        return ZodParsedType.object;
      default:
        return ZodParsedType.unknown;
    }
  };

  // node_modules/.pnpm/zod@3.25.76/node_modules/zod/v3/ZodError.js
  var ZodIssueCode = util.arrayToEnum([
    "invalid_type",
    "invalid_literal",
    "custom",
    "invalid_union",
    "invalid_union_discriminator",
    "invalid_enum_value",
    "unrecognized_keys",
    "invalid_arguments",
    "invalid_return_type",
    "invalid_date",
    "invalid_string",
    "too_small",
    "too_big",
    "invalid_intersection_types",
    "not_multiple_of",
    "not_finite"
  ]);
  var quotelessJson = (obj) => {
    const json = JSON.stringify(obj, null, 2);
    return json.replace(/"([^"]+)":/g, "$1:");
  };
  var ZodError = class _ZodError extends Error {
    get errors() {
      return this.issues;
    }
    constructor(issues) {
      super();
      this.issues = [];
      this.addIssue = (sub) => {
        this.issues = [...this.issues, sub];
      };
      this.addIssues = (subs = []) => {
        this.issues = [...this.issues, ...subs];
      };
      const actualProto = new.target.prototype;
      if (Object.setPrototypeOf) {
        Object.setPrototypeOf(this, actualProto);
      } else {
        this.__proto__ = actualProto;
      }
      this.name = "ZodError";
      this.issues = issues;
    }
    format(_mapper) {
      const mapper = _mapper || function(issue) {
        return issue.message;
      };
      const fieldErrors = { _errors: [] };
      const processError = (error) => {
        for (const issue of error.issues) {
          if (issue.code === "invalid_union") {
            issue.unionErrors.map(processError);
          } else if (issue.code === "invalid_return_type") {
            processError(issue.returnTypeError);
          } else if (issue.code === "invalid_arguments") {
            processError(issue.argumentsError);
          } else if (issue.path.length === 0) {
            fieldErrors._errors.push(mapper(issue));
          } else {
            let curr = fieldErrors;
            let i = 0;
            while (i < issue.path.length) {
              const el = issue.path[i];
              const terminal = i === issue.path.length - 1;
              if (!terminal) {
                curr[el] = curr[el] || { _errors: [] };
              } else {
                curr[el] = curr[el] || { _errors: [] };
                curr[el]._errors.push(mapper(issue));
              }
              curr = curr[el];
              i++;
            }
          }
        }
      };
      processError(this);
      return fieldErrors;
    }
    static assert(value) {
      if (!(value instanceof _ZodError)) {
        throw new Error(`Not a ZodError: ${value}`);
      }
    }
    toString() {
      return this.message;
    }
    get message() {
      return JSON.stringify(this.issues, util.jsonStringifyReplacer, 2);
    }
    get isEmpty() {
      return this.issues.length === 0;
    }
    flatten(mapper = (issue) => issue.message) {
      const fieldErrors = {};
      const formErrors = [];
      for (const sub of this.issues) {
        if (sub.path.length > 0) {
          const firstEl = sub.path[0];
          fieldErrors[firstEl] = fieldErrors[firstEl] || [];
          fieldErrors[firstEl].push(mapper(sub));
        } else {
          formErrors.push(mapper(sub));
        }
      }
      return { formErrors, fieldErrors };
    }
    get formErrors() {
      return this.flatten();
    }
  };
  ZodError.create = (issues) => {
    const error = new ZodError(issues);
    return error;
  };

  // node_modules/.pnpm/zod@3.25.76/node_modules/zod/v3/locales/en.js
  var errorMap = (issue, _ctx) => {
    let message;
    switch (issue.code) {
      case ZodIssueCode.invalid_type:
        if (issue.received === ZodParsedType.undefined) {
          message = "Required";
        } else {
          message = `Expected ${issue.expected}, received ${issue.received}`;
        }
        break;
      case ZodIssueCode.invalid_literal:
        message = `Invalid literal value, expected ${JSON.stringify(issue.expected, util.jsonStringifyReplacer)}`;
        break;
      case ZodIssueCode.unrecognized_keys:
        message = `Unrecognized key(s) in object: ${util.joinValues(issue.keys, ", ")}`;
        break;
      case ZodIssueCode.invalid_union:
        message = `Invalid input`;
        break;
      case ZodIssueCode.invalid_union_discriminator:
        message = `Invalid discriminator value. Expected ${util.joinValues(issue.options)}`;
        break;
      case ZodIssueCode.invalid_enum_value:
        message = `Invalid enum value. Expected ${util.joinValues(issue.options)}, received '${issue.received}'`;
        break;
      case ZodIssueCode.invalid_arguments:
        message = `Invalid function arguments`;
        break;
      case ZodIssueCode.invalid_return_type:
        message = `Invalid function return type`;
        break;
      case ZodIssueCode.invalid_date:
        message = `Invalid date`;
        break;
      case ZodIssueCode.invalid_string:
        if (typeof issue.validation === "object") {
          if ("includes" in issue.validation) {
            message = `Invalid input: must include "${issue.validation.includes}"`;
            if (typeof issue.validation.position === "number") {
              message = `${message} at one or more positions greater than or equal to ${issue.validation.position}`;
            }
          } else if ("startsWith" in issue.validation) {
            message = `Invalid input: must start with "${issue.validation.startsWith}"`;
          } else if ("endsWith" in issue.validation) {
            message = `Invalid input: must end with "${issue.validation.endsWith}"`;
          } else {
            util.assertNever(issue.validation);
          }
        } else if (issue.validation !== "regex") {
          message = `Invalid ${issue.validation}`;
        } else {
          message = "Invalid";
        }
        break;
      case ZodIssueCode.too_small:
        if (issue.type === "array")
          message = `Array must contain ${issue.exact ? "exactly" : issue.inclusive ? `at least` : `more than`} ${issue.minimum} element(s)`;
        else if (issue.type === "string")
          message = `String must contain ${issue.exact ? "exactly" : issue.inclusive ? `at least` : `over`} ${issue.minimum} character(s)`;
        else if (issue.type === "number")
          message = `Number must be ${issue.exact ? `exactly equal to ` : issue.inclusive ? `greater than or equal to ` : `greater than `}${issue.minimum}`;
        else if (issue.type === "bigint")
          message = `Number must be ${issue.exact ? `exactly equal to ` : issue.inclusive ? `greater than or equal to ` : `greater than `}${issue.minimum}`;
        else if (issue.type === "date")
          message = `Date must be ${issue.exact ? `exactly equal to ` : issue.inclusive ? `greater than or equal to ` : `greater than `}${new Date(Number(issue.minimum))}`;
        else
          message = "Invalid input";
        break;
      case ZodIssueCode.too_big:
        if (issue.type === "array")
          message = `Array must contain ${issue.exact ? `exactly` : issue.inclusive ? `at most` : `less than`} ${issue.maximum} element(s)`;
        else if (issue.type === "string")
          message = `String must contain ${issue.exact ? `exactly` : issue.inclusive ? `at most` : `under`} ${issue.maximum} character(s)`;
        else if (issue.type === "number")
          message = `Number must be ${issue.exact ? `exactly` : issue.inclusive ? `less than or equal to` : `less than`} ${issue.maximum}`;
        else if (issue.type === "bigint")
          message = `BigInt must be ${issue.exact ? `exactly` : issue.inclusive ? `less than or equal to` : `less than`} ${issue.maximum}`;
        else if (issue.type === "date")
          message = `Date must be ${issue.exact ? `exactly` : issue.inclusive ? `smaller than or equal to` : `smaller than`} ${new Date(Number(issue.maximum))}`;
        else
          message = "Invalid input";
        break;
      case ZodIssueCode.custom:
        message = `Invalid input`;
        break;
      case ZodIssueCode.invalid_intersection_types:
        message = `Intersection results could not be merged`;
        break;
      case ZodIssueCode.not_multiple_of:
        message = `Number must be a multiple of ${issue.multipleOf}`;
        break;
      case ZodIssueCode.not_finite:
        message = "Number must be finite";
        break;
      default:
        message = _ctx.defaultError;
        util.assertNever(issue);
    }
    return { message };
  };
  var en_default = errorMap;

  // node_modules/.pnpm/zod@3.25.76/node_modules/zod/v3/errors.js
  var overrideErrorMap = en_default;
  function setErrorMap(map) {
    overrideErrorMap = map;
  }
  function getErrorMap() {
    return overrideErrorMap;
  }

  // node_modules/.pnpm/zod@3.25.76/node_modules/zod/v3/helpers/parseUtil.js
  var makeIssue = (params) => {
    const { data, path, errorMaps, issueData } = params;
    const fullPath = [...path, ...issueData.path || []];
    const fullIssue = {
      ...issueData,
      path: fullPath
    };
    if (issueData.message !== void 0) {
      return {
        ...issueData,
        path: fullPath,
        message: issueData.message
      };
    }
    let errorMessage = "";
    const maps = errorMaps.filter((m) => !!m).slice().reverse();
    for (const map of maps) {
      errorMessage = map(fullIssue, { data, defaultError: errorMessage }).message;
    }
    return {
      ...issueData,
      path: fullPath,
      message: errorMessage
    };
  };
  var EMPTY_PATH = [];
  function addIssueToContext(ctx, issueData) {
    const overrideMap = getErrorMap();
    const issue = makeIssue({
      issueData,
      data: ctx.data,
      path: ctx.path,
      errorMaps: [
        ctx.common.contextualErrorMap,
        // contextual error map is first priority
        ctx.schemaErrorMap,
        // then schema-bound map if available
        overrideMap,
        // then global override map
        overrideMap === en_default ? void 0 : en_default
        // then global default map
      ].filter((x) => !!x)
    });
    ctx.common.issues.push(issue);
  }
  var ParseStatus = class _ParseStatus {
    constructor() {
      this.value = "valid";
    }
    dirty() {
      if (this.value === "valid")
        this.value = "dirty";
    }
    abort() {
      if (this.value !== "aborted")
        this.value = "aborted";
    }
    static mergeArray(status, results) {
      const arrayValue = [];
      for (const s of results) {
        if (s.status === "aborted")
          return INVALID;
        if (s.status === "dirty")
          status.dirty();
        arrayValue.push(s.value);
      }
      return { status: status.value, value: arrayValue };
    }
    static async mergeObjectAsync(status, pairs) {
      const syncPairs = [];
      for (const pair of pairs) {
        const key = await pair.key;
        const value = await pair.value;
        syncPairs.push({
          key,
          value
        });
      }
      return _ParseStatus.mergeObjectSync(status, syncPairs);
    }
    static mergeObjectSync(status, pairs) {
      const finalObject = {};
      for (const pair of pairs) {
        const { key, value } = pair;
        if (key.status === "aborted")
          return INVALID;
        if (value.status === "aborted")
          return INVALID;
        if (key.status === "dirty")
          status.dirty();
        if (value.status === "dirty")
          status.dirty();
        if (key.value !== "__proto__" && (typeof value.value !== "undefined" || pair.alwaysSet)) {
          finalObject[key.value] = value.value;
        }
      }
      return { status: status.value, value: finalObject };
    }
  };
  var INVALID = Object.freeze({
    status: "aborted"
  });
  var DIRTY = (value) => ({ status: "dirty", value });
  var OK = (value) => ({ status: "valid", value });
  var isAborted = (x) => x.status === "aborted";
  var isDirty = (x) => x.status === "dirty";
  var isValid = (x) => x.status === "valid";
  var isAsync = (x) => typeof Promise !== "undefined" && x instanceof Promise;

  // node_modules/.pnpm/zod@3.25.76/node_modules/zod/v3/helpers/errorUtil.js
  var errorUtil;
  (function(errorUtil2) {
    errorUtil2.errToObj = (message) => typeof message === "string" ? { message } : message || {};
    errorUtil2.toString = (message) => typeof message === "string" ? message : message == null ? void 0 : message.message;
  })(errorUtil || (errorUtil = {}));

  // node_modules/.pnpm/zod@3.25.76/node_modules/zod/v3/types.js
  var ParseInputLazyPath = class {
    constructor(parent, value, path, key) {
      this._cachedPath = [];
      this.parent = parent;
      this.data = value;
      this._path = path;
      this._key = key;
    }
    get path() {
      if (!this._cachedPath.length) {
        if (Array.isArray(this._key)) {
          this._cachedPath.push(...this._path, ...this._key);
        } else {
          this._cachedPath.push(...this._path, this._key);
        }
      }
      return this._cachedPath;
    }
  };
  var handleResult = (ctx, result) => {
    if (isValid(result)) {
      return { success: true, data: result.value };
    } else {
      if (!ctx.common.issues.length) {
        throw new Error("Validation failed but no issues detected.");
      }
      return {
        success: false,
        get error() {
          if (this._error)
            return this._error;
          const error = new ZodError(ctx.common.issues);
          this._error = error;
          return this._error;
        }
      };
    }
  };
  function processCreateParams(params) {
    if (!params)
      return {};
    const { errorMap: errorMap2, invalid_type_error, required_error, description } = params;
    if (errorMap2 && (invalid_type_error || required_error)) {
      throw new Error(`Can't use "invalid_type_error" or "required_error" in conjunction with custom error map.`);
    }
    if (errorMap2)
      return { errorMap: errorMap2, description };
    const customMap = (iss, ctx) => {
      var _a, _b;
      const { message } = params;
      if (iss.code === "invalid_enum_value") {
        return { message: message != null ? message : ctx.defaultError };
      }
      if (typeof ctx.data === "undefined") {
        return { message: (_a = message != null ? message : required_error) != null ? _a : ctx.defaultError };
      }
      if (iss.code !== "invalid_type")
        return { message: ctx.defaultError };
      return { message: (_b = message != null ? message : invalid_type_error) != null ? _b : ctx.defaultError };
    };
    return { errorMap: customMap, description };
  }
  var ZodType = class {
    get description() {
      return this._def.description;
    }
    _getType(input) {
      return getParsedType(input.data);
    }
    _getOrReturnCtx(input, ctx) {
      return ctx || {
        common: input.parent.common,
        data: input.data,
        parsedType: getParsedType(input.data),
        schemaErrorMap: this._def.errorMap,
        path: input.path,
        parent: input.parent
      };
    }
    _processInputParams(input) {
      return {
        status: new ParseStatus(),
        ctx: {
          common: input.parent.common,
          data: input.data,
          parsedType: getParsedType(input.data),
          schemaErrorMap: this._def.errorMap,
          path: input.path,
          parent: input.parent
        }
      };
    }
    _parseSync(input) {
      const result = this._parse(input);
      if (isAsync(result)) {
        throw new Error("Synchronous parse encountered promise.");
      }
      return result;
    }
    _parseAsync(input) {
      const result = this._parse(input);
      return Promise.resolve(result);
    }
    parse(data, params) {
      const result = this.safeParse(data, params);
      if (result.success)
        return result.data;
      throw result.error;
    }
    safeParse(data, params) {
      var _a;
      const ctx = {
        common: {
          issues: [],
          async: (_a = params == null ? void 0 : params.async) != null ? _a : false,
          contextualErrorMap: params == null ? void 0 : params.errorMap
        },
        path: (params == null ? void 0 : params.path) || [],
        schemaErrorMap: this._def.errorMap,
        parent: null,
        data,
        parsedType: getParsedType(data)
      };
      const result = this._parseSync({ data, path: ctx.path, parent: ctx });
      return handleResult(ctx, result);
    }
    "~validate"(data) {
      var _a, _b;
      const ctx = {
        common: {
          issues: [],
          async: !!this["~standard"].async
        },
        path: [],
        schemaErrorMap: this._def.errorMap,
        parent: null,
        data,
        parsedType: getParsedType(data)
      };
      if (!this["~standard"].async) {
        try {
          const result = this._parseSync({ data, path: [], parent: ctx });
          return isValid(result) ? {
            value: result.value
          } : {
            issues: ctx.common.issues
          };
        } catch (err) {
          if ((_b = (_a = err == null ? void 0 : err.message) == null ? void 0 : _a.toLowerCase()) == null ? void 0 : _b.includes("encountered")) {
            this["~standard"].async = true;
          }
          ctx.common = {
            issues: [],
            async: true
          };
        }
      }
      return this._parseAsync({ data, path: [], parent: ctx }).then((result) => isValid(result) ? {
        value: result.value
      } : {
        issues: ctx.common.issues
      });
    }
    async parseAsync(data, params) {
      const result = await this.safeParseAsync(data, params);
      if (result.success)
        return result.data;
      throw result.error;
    }
    async safeParseAsync(data, params) {
      const ctx = {
        common: {
          issues: [],
          contextualErrorMap: params == null ? void 0 : params.errorMap,
          async: true
        },
        path: (params == null ? void 0 : params.path) || [],
        schemaErrorMap: this._def.errorMap,
        parent: null,
        data,
        parsedType: getParsedType(data)
      };
      const maybeAsyncResult = this._parse({ data, path: ctx.path, parent: ctx });
      const result = await (isAsync(maybeAsyncResult) ? maybeAsyncResult : Promise.resolve(maybeAsyncResult));
      return handleResult(ctx, result);
    }
    refine(check, message) {
      const getIssueProperties = (val) => {
        if (typeof message === "string" || typeof message === "undefined") {
          return { message };
        } else if (typeof message === "function") {
          return message(val);
        } else {
          return message;
        }
      };
      return this._refinement((val, ctx) => {
        const result = check(val);
        const setError = () => ctx.addIssue({
          code: ZodIssueCode.custom,
          ...getIssueProperties(val)
        });
        if (typeof Promise !== "undefined" && result instanceof Promise) {
          return result.then((data) => {
            if (!data) {
              setError();
              return false;
            } else {
              return true;
            }
          });
        }
        if (!result) {
          setError();
          return false;
        } else {
          return true;
        }
      });
    }
    refinement(check, refinementData) {
      return this._refinement((val, ctx) => {
        if (!check(val)) {
          ctx.addIssue(typeof refinementData === "function" ? refinementData(val, ctx) : refinementData);
          return false;
        } else {
          return true;
        }
      });
    }
    _refinement(refinement) {
      return new ZodEffects({
        schema: this,
        typeName: ZodFirstPartyTypeKind.ZodEffects,
        effect: { type: "refinement", refinement }
      });
    }
    superRefine(refinement) {
      return this._refinement(refinement);
    }
    constructor(def) {
      this.spa = this.safeParseAsync;
      this._def = def;
      this.parse = this.parse.bind(this);
      this.safeParse = this.safeParse.bind(this);
      this.parseAsync = this.parseAsync.bind(this);
      this.safeParseAsync = this.safeParseAsync.bind(this);
      this.spa = this.spa.bind(this);
      this.refine = this.refine.bind(this);
      this.refinement = this.refinement.bind(this);
      this.superRefine = this.superRefine.bind(this);
      this.optional = this.optional.bind(this);
      this.nullable = this.nullable.bind(this);
      this.nullish = this.nullish.bind(this);
      this.array = this.array.bind(this);
      this.promise = this.promise.bind(this);
      this.or = this.or.bind(this);
      this.and = this.and.bind(this);
      this.transform = this.transform.bind(this);
      this.brand = this.brand.bind(this);
      this.default = this.default.bind(this);
      this.catch = this.catch.bind(this);
      this.describe = this.describe.bind(this);
      this.pipe = this.pipe.bind(this);
      this.readonly = this.readonly.bind(this);
      this.isNullable = this.isNullable.bind(this);
      this.isOptional = this.isOptional.bind(this);
      this["~standard"] = {
        version: 1,
        vendor: "zod",
        validate: (data) => this["~validate"](data)
      };
    }
    optional() {
      return ZodOptional.create(this, this._def);
    }
    nullable() {
      return ZodNullable.create(this, this._def);
    }
    nullish() {
      return this.nullable().optional();
    }
    array() {
      return ZodArray.create(this);
    }
    promise() {
      return ZodPromise.create(this, this._def);
    }
    or(option) {
      return ZodUnion.create([this, option], this._def);
    }
    and(incoming) {
      return ZodIntersection.create(this, incoming, this._def);
    }
    transform(transform) {
      return new ZodEffects({
        ...processCreateParams(this._def),
        schema: this,
        typeName: ZodFirstPartyTypeKind.ZodEffects,
        effect: { type: "transform", transform }
      });
    }
    default(def) {
      const defaultValueFunc = typeof def === "function" ? def : () => def;
      return new ZodDefault({
        ...processCreateParams(this._def),
        innerType: this,
        defaultValue: defaultValueFunc,
        typeName: ZodFirstPartyTypeKind.ZodDefault
      });
    }
    brand() {
      return new ZodBranded({
        typeName: ZodFirstPartyTypeKind.ZodBranded,
        type: this,
        ...processCreateParams(this._def)
      });
    }
    catch(def) {
      const catchValueFunc = typeof def === "function" ? def : () => def;
      return new ZodCatch({
        ...processCreateParams(this._def),
        innerType: this,
        catchValue: catchValueFunc,
        typeName: ZodFirstPartyTypeKind.ZodCatch
      });
    }
    describe(description) {
      const This = this.constructor;
      return new This({
        ...this._def,
        description
      });
    }
    pipe(target) {
      return ZodPipeline.create(this, target);
    }
    readonly() {
      return ZodReadonly.create(this);
    }
    isOptional() {
      return this.safeParse(void 0).success;
    }
    isNullable() {
      return this.safeParse(null).success;
    }
  };
  var cuidRegex = /^c[^\s-]{8,}$/i;
  var cuid2Regex = /^[0-9a-z]+$/;
  var ulidRegex = /^[0-9A-HJKMNP-TV-Z]{26}$/i;
  var uuidRegex = /^[0-9a-fA-F]{8}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{12}$/i;
  var nanoidRegex = /^[a-z0-9_-]{21}$/i;
  var jwtRegex = /^[A-Za-z0-9-_]+\.[A-Za-z0-9-_]+\.[A-Za-z0-9-_]*$/;
  var durationRegex = /^[-+]?P(?!$)(?:(?:[-+]?\d+Y)|(?:[-+]?\d+[.,]\d+Y$))?(?:(?:[-+]?\d+M)|(?:[-+]?\d+[.,]\d+M$))?(?:(?:[-+]?\d+W)|(?:[-+]?\d+[.,]\d+W$))?(?:(?:[-+]?\d+D)|(?:[-+]?\d+[.,]\d+D$))?(?:T(?=[\d+-])(?:(?:[-+]?\d+H)|(?:[-+]?\d+[.,]\d+H$))?(?:(?:[-+]?\d+M)|(?:[-+]?\d+[.,]\d+M$))?(?:[-+]?\d+(?:[.,]\d+)?S)?)??$/;
  var emailRegex = /^(?!\.)(?!.*\.\.)([A-Z0-9_'+\-\.]*)[A-Z0-9_+-]@([A-Z0-9][A-Z0-9\-]*\.)+[A-Z]{2,}$/i;
  var _emojiRegex = `^(\\p{Extended_Pictographic}|\\p{Emoji_Component})+$`;
  var emojiRegex;
  var ipv4Regex = /^(?:(?:25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9][0-9]|[0-9])\.){3}(?:25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9][0-9]|[0-9])$/;
  var ipv4CidrRegex = /^(?:(?:25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9][0-9]|[0-9])\.){3}(?:25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9][0-9]|[0-9])\/(3[0-2]|[12]?[0-9])$/;
  var ipv6Regex = /^(([0-9a-fA-F]{1,4}:){7,7}[0-9a-fA-F]{1,4}|([0-9a-fA-F]{1,4}:){1,7}:|([0-9a-fA-F]{1,4}:){1,6}:[0-9a-fA-F]{1,4}|([0-9a-fA-F]{1,4}:){1,5}(:[0-9a-fA-F]{1,4}){1,2}|([0-9a-fA-F]{1,4}:){1,4}(:[0-9a-fA-F]{1,4}){1,3}|([0-9a-fA-F]{1,4}:){1,3}(:[0-9a-fA-F]{1,4}){1,4}|([0-9a-fA-F]{1,4}:){1,2}(:[0-9a-fA-F]{1,4}){1,5}|[0-9a-fA-F]{1,4}:((:[0-9a-fA-F]{1,4}){1,6})|:((:[0-9a-fA-F]{1,4}){1,7}|:)|fe80:(:[0-9a-fA-F]{0,4}){0,4}%[0-9a-zA-Z]{1,}|::(ffff(:0{1,4}){0,1}:){0,1}((25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])\.){3,3}(25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])|([0-9a-fA-F]{1,4}:){1,4}:((25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])\.){3,3}(25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9]))$/;
  var ipv6CidrRegex = /^(([0-9a-fA-F]{1,4}:){7,7}[0-9a-fA-F]{1,4}|([0-9a-fA-F]{1,4}:){1,7}:|([0-9a-fA-F]{1,4}:){1,6}:[0-9a-fA-F]{1,4}|([0-9a-fA-F]{1,4}:){1,5}(:[0-9a-fA-F]{1,4}){1,2}|([0-9a-fA-F]{1,4}:){1,4}(:[0-9a-fA-F]{1,4}){1,3}|([0-9a-fA-F]{1,4}:){1,3}(:[0-9a-fA-F]{1,4}){1,4}|([0-9a-fA-F]{1,4}:){1,2}(:[0-9a-fA-F]{1,4}){1,5}|[0-9a-fA-F]{1,4}:((:[0-9a-fA-F]{1,4}){1,6})|:((:[0-9a-fA-F]{1,4}){1,7}|:)|fe80:(:[0-9a-fA-F]{0,4}){0,4}%[0-9a-zA-Z]{1,}|::(ffff(:0{1,4}){0,1}:){0,1}((25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])\.){3,3}(25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])|([0-9a-fA-F]{1,4}:){1,4}:((25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])\.){3,3}(25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9]))\/(12[0-8]|1[01][0-9]|[1-9]?[0-9])$/;
  var base64Regex = /^([0-9a-zA-Z+/]{4})*(([0-9a-zA-Z+/]{2}==)|([0-9a-zA-Z+/]{3}=))?$/;
  var base64urlRegex = /^([0-9a-zA-Z-_]{4})*(([0-9a-zA-Z-_]{2}(==)?)|([0-9a-zA-Z-_]{3}(=)?))?$/;
  var dateRegexSource = `((\\d\\d[2468][048]|\\d\\d[13579][26]|\\d\\d0[48]|[02468][048]00|[13579][26]00)-02-29|\\d{4}-((0[13578]|1[02])-(0[1-9]|[12]\\d|3[01])|(0[469]|11)-(0[1-9]|[12]\\d|30)|(02)-(0[1-9]|1\\d|2[0-8])))`;
  var dateRegex = new RegExp(`^${dateRegexSource}$`);
  function timeRegexSource(args) {
    let secondsRegexSource = `[0-5]\\d`;
    if (args.precision) {
      secondsRegexSource = `${secondsRegexSource}\\.\\d{${args.precision}}`;
    } else if (args.precision == null) {
      secondsRegexSource = `${secondsRegexSource}(\\.\\d+)?`;
    }
    const secondsQuantifier = args.precision ? "+" : "?";
    return `([01]\\d|2[0-3]):[0-5]\\d(:${secondsRegexSource})${secondsQuantifier}`;
  }
  function timeRegex(args) {
    return new RegExp(`^${timeRegexSource(args)}$`);
  }
  function datetimeRegex(args) {
    let regex = `${dateRegexSource}T${timeRegexSource(args)}`;
    const opts = [];
    opts.push(args.local ? `Z?` : `Z`);
    if (args.offset)
      opts.push(`([+-]\\d{2}:?\\d{2})`);
    regex = `${regex}(${opts.join("|")})`;
    return new RegExp(`^${regex}$`);
  }
  function isValidIP(ip, version) {
    if ((version === "v4" || !version) && ipv4Regex.test(ip)) {
      return true;
    }
    if ((version === "v6" || !version) && ipv6Regex.test(ip)) {
      return true;
    }
    return false;
  }
  function isValidJWT(jwt, alg) {
    if (!jwtRegex.test(jwt))
      return false;
    try {
      const [header] = jwt.split(".");
      if (!header)
        return false;
      const base64 = header.replace(/-/g, "+").replace(/_/g, "/").padEnd(header.length + (4 - header.length % 4) % 4, "=");
      const decoded = JSON.parse(atob(base64));
      if (typeof decoded !== "object" || decoded === null)
        return false;
      if ("typ" in decoded && (decoded == null ? void 0 : decoded.typ) !== "JWT")
        return false;
      if (!decoded.alg)
        return false;
      if (alg && decoded.alg !== alg)
        return false;
      return true;
    } catch {
      return false;
    }
  }
  function isValidCidr(ip, version) {
    if ((version === "v4" || !version) && ipv4CidrRegex.test(ip)) {
      return true;
    }
    if ((version === "v6" || !version) && ipv6CidrRegex.test(ip)) {
      return true;
    }
    return false;
  }
  var ZodString = class _ZodString extends ZodType {
    _parse(input) {
      if (this._def.coerce) {
        input.data = String(input.data);
      }
      const parsedType = this._getType(input);
      if (parsedType !== ZodParsedType.string) {
        const ctx2 = this._getOrReturnCtx(input);
        addIssueToContext(ctx2, {
          code: ZodIssueCode.invalid_type,
          expected: ZodParsedType.string,
          received: ctx2.parsedType
        });
        return INVALID;
      }
      const status = new ParseStatus();
      let ctx = void 0;
      for (const check of this._def.checks) {
        if (check.kind === "min") {
          if (input.data.length < check.value) {
            ctx = this._getOrReturnCtx(input, ctx);
            addIssueToContext(ctx, {
              code: ZodIssueCode.too_small,
              minimum: check.value,
              type: "string",
              inclusive: true,
              exact: false,
              message: check.message
            });
            status.dirty();
          }
        } else if (check.kind === "max") {
          if (input.data.length > check.value) {
            ctx = this._getOrReturnCtx(input, ctx);
            addIssueToContext(ctx, {
              code: ZodIssueCode.too_big,
              maximum: check.value,
              type: "string",
              inclusive: true,
              exact: false,
              message: check.message
            });
            status.dirty();
          }
        } else if (check.kind === "length") {
          const tooBig = input.data.length > check.value;
          const tooSmall = input.data.length < check.value;
          if (tooBig || tooSmall) {
            ctx = this._getOrReturnCtx(input, ctx);
            if (tooBig) {
              addIssueToContext(ctx, {
                code: ZodIssueCode.too_big,
                maximum: check.value,
                type: "string",
                inclusive: true,
                exact: true,
                message: check.message
              });
            } else if (tooSmall) {
              addIssueToContext(ctx, {
                code: ZodIssueCode.too_small,
                minimum: check.value,
                type: "string",
                inclusive: true,
                exact: true,
                message: check.message
              });
            }
            status.dirty();
          }
        } else if (check.kind === "email") {
          if (!emailRegex.test(input.data)) {
            ctx = this._getOrReturnCtx(input, ctx);
            addIssueToContext(ctx, {
              validation: "email",
              code: ZodIssueCode.invalid_string,
              message: check.message
            });
            status.dirty();
          }
        } else if (check.kind === "emoji") {
          if (!emojiRegex) {
            emojiRegex = new RegExp(_emojiRegex, "u");
          }
          if (!emojiRegex.test(input.data)) {
            ctx = this._getOrReturnCtx(input, ctx);
            addIssueToContext(ctx, {
              validation: "emoji",
              code: ZodIssueCode.invalid_string,
              message: check.message
            });
            status.dirty();
          }
        } else if (check.kind === "uuid") {
          if (!uuidRegex.test(input.data)) {
            ctx = this._getOrReturnCtx(input, ctx);
            addIssueToContext(ctx, {
              validation: "uuid",
              code: ZodIssueCode.invalid_string,
              message: check.message
            });
            status.dirty();
          }
        } else if (check.kind === "nanoid") {
          if (!nanoidRegex.test(input.data)) {
            ctx = this._getOrReturnCtx(input, ctx);
            addIssueToContext(ctx, {
              validation: "nanoid",
              code: ZodIssueCode.invalid_string,
              message: check.message
            });
            status.dirty();
          }
        } else if (check.kind === "cuid") {
          if (!cuidRegex.test(input.data)) {
            ctx = this._getOrReturnCtx(input, ctx);
            addIssueToContext(ctx, {
              validation: "cuid",
              code: ZodIssueCode.invalid_string,
              message: check.message
            });
            status.dirty();
          }
        } else if (check.kind === "cuid2") {
          if (!cuid2Regex.test(input.data)) {
            ctx = this._getOrReturnCtx(input, ctx);
            addIssueToContext(ctx, {
              validation: "cuid2",
              code: ZodIssueCode.invalid_string,
              message: check.message
            });
            status.dirty();
          }
        } else if (check.kind === "ulid") {
          if (!ulidRegex.test(input.data)) {
            ctx = this._getOrReturnCtx(input, ctx);
            addIssueToContext(ctx, {
              validation: "ulid",
              code: ZodIssueCode.invalid_string,
              message: check.message
            });
            status.dirty();
          }
        } else if (check.kind === "url") {
          try {
            new URL(input.data);
          } catch {
            ctx = this._getOrReturnCtx(input, ctx);
            addIssueToContext(ctx, {
              validation: "url",
              code: ZodIssueCode.invalid_string,
              message: check.message
            });
            status.dirty();
          }
        } else if (check.kind === "regex") {
          check.regex.lastIndex = 0;
          const testResult = check.regex.test(input.data);
          if (!testResult) {
            ctx = this._getOrReturnCtx(input, ctx);
            addIssueToContext(ctx, {
              validation: "regex",
              code: ZodIssueCode.invalid_string,
              message: check.message
            });
            status.dirty();
          }
        } else if (check.kind === "trim") {
          input.data = input.data.trim();
        } else if (check.kind === "includes") {
          if (!input.data.includes(check.value, check.position)) {
            ctx = this._getOrReturnCtx(input, ctx);
            addIssueToContext(ctx, {
              code: ZodIssueCode.invalid_string,
              validation: { includes: check.value, position: check.position },
              message: check.message
            });
            status.dirty();
          }
        } else if (check.kind === "toLowerCase") {
          input.data = input.data.toLowerCase();
        } else if (check.kind === "toUpperCase") {
          input.data = input.data.toUpperCase();
        } else if (check.kind === "startsWith") {
          if (!input.data.startsWith(check.value)) {
            ctx = this._getOrReturnCtx(input, ctx);
            addIssueToContext(ctx, {
              code: ZodIssueCode.invalid_string,
              validation: { startsWith: check.value },
              message: check.message
            });
            status.dirty();
          }
        } else if (check.kind === "endsWith") {
          if (!input.data.endsWith(check.value)) {
            ctx = this._getOrReturnCtx(input, ctx);
            addIssueToContext(ctx, {
              code: ZodIssueCode.invalid_string,
              validation: { endsWith: check.value },
              message: check.message
            });
            status.dirty();
          }
        } else if (check.kind === "datetime") {
          const regex = datetimeRegex(check);
          if (!regex.test(input.data)) {
            ctx = this._getOrReturnCtx(input, ctx);
            addIssueToContext(ctx, {
              code: ZodIssueCode.invalid_string,
              validation: "datetime",
              message: check.message
            });
            status.dirty();
          }
        } else if (check.kind === "date") {
          const regex = dateRegex;
          if (!regex.test(input.data)) {
            ctx = this._getOrReturnCtx(input, ctx);
            addIssueToContext(ctx, {
              code: ZodIssueCode.invalid_string,
              validation: "date",
              message: check.message
            });
            status.dirty();
          }
        } else if (check.kind === "time") {
          const regex = timeRegex(check);
          if (!regex.test(input.data)) {
            ctx = this._getOrReturnCtx(input, ctx);
            addIssueToContext(ctx, {
              code: ZodIssueCode.invalid_string,
              validation: "time",
              message: check.message
            });
            status.dirty();
          }
        } else if (check.kind === "duration") {
          if (!durationRegex.test(input.data)) {
            ctx = this._getOrReturnCtx(input, ctx);
            addIssueToContext(ctx, {
              validation: "duration",
              code: ZodIssueCode.invalid_string,
              message: check.message
            });
            status.dirty();
          }
        } else if (check.kind === "ip") {
          if (!isValidIP(input.data, check.version)) {
            ctx = this._getOrReturnCtx(input, ctx);
            addIssueToContext(ctx, {
              validation: "ip",
              code: ZodIssueCode.invalid_string,
              message: check.message
            });
            status.dirty();
          }
        } else if (check.kind === "jwt") {
          if (!isValidJWT(input.data, check.alg)) {
            ctx = this._getOrReturnCtx(input, ctx);
            addIssueToContext(ctx, {
              validation: "jwt",
              code: ZodIssueCode.invalid_string,
              message: check.message
            });
            status.dirty();
          }
        } else if (check.kind === "cidr") {
          if (!isValidCidr(input.data, check.version)) {
            ctx = this._getOrReturnCtx(input, ctx);
            addIssueToContext(ctx, {
              validation: "cidr",
              code: ZodIssueCode.invalid_string,
              message: check.message
            });
            status.dirty();
          }
        } else if (check.kind === "base64") {
          if (!base64Regex.test(input.data)) {
            ctx = this._getOrReturnCtx(input, ctx);
            addIssueToContext(ctx, {
              validation: "base64",
              code: ZodIssueCode.invalid_string,
              message: check.message
            });
            status.dirty();
          }
        } else if (check.kind === "base64url") {
          if (!base64urlRegex.test(input.data)) {
            ctx = this._getOrReturnCtx(input, ctx);
            addIssueToContext(ctx, {
              validation: "base64url",
              code: ZodIssueCode.invalid_string,
              message: check.message
            });
            status.dirty();
          }
        } else {
          util.assertNever(check);
        }
      }
      return { status: status.value, value: input.data };
    }
    _regex(regex, validation, message) {
      return this.refinement((data) => regex.test(data), {
        validation,
        code: ZodIssueCode.invalid_string,
        ...errorUtil.errToObj(message)
      });
    }
    _addCheck(check) {
      return new _ZodString({
        ...this._def,
        checks: [...this._def.checks, check]
      });
    }
    email(message) {
      return this._addCheck({ kind: "email", ...errorUtil.errToObj(message) });
    }
    url(message) {
      return this._addCheck({ kind: "url", ...errorUtil.errToObj(message) });
    }
    emoji(message) {
      return this._addCheck({ kind: "emoji", ...errorUtil.errToObj(message) });
    }
    uuid(message) {
      return this._addCheck({ kind: "uuid", ...errorUtil.errToObj(message) });
    }
    nanoid(message) {
      return this._addCheck({ kind: "nanoid", ...errorUtil.errToObj(message) });
    }
    cuid(message) {
      return this._addCheck({ kind: "cuid", ...errorUtil.errToObj(message) });
    }
    cuid2(message) {
      return this._addCheck({ kind: "cuid2", ...errorUtil.errToObj(message) });
    }
    ulid(message) {
      return this._addCheck({ kind: "ulid", ...errorUtil.errToObj(message) });
    }
    base64(message) {
      return this._addCheck({ kind: "base64", ...errorUtil.errToObj(message) });
    }
    base64url(message) {
      return this._addCheck({
        kind: "base64url",
        ...errorUtil.errToObj(message)
      });
    }
    jwt(options) {
      return this._addCheck({ kind: "jwt", ...errorUtil.errToObj(options) });
    }
    ip(options) {
      return this._addCheck({ kind: "ip", ...errorUtil.errToObj(options) });
    }
    cidr(options) {
      return this._addCheck({ kind: "cidr", ...errorUtil.errToObj(options) });
    }
    datetime(options) {
      var _a, _b;
      if (typeof options === "string") {
        return this._addCheck({
          kind: "datetime",
          precision: null,
          offset: false,
          local: false,
          message: options
        });
      }
      return this._addCheck({
        kind: "datetime",
        precision: typeof (options == null ? void 0 : options.precision) === "undefined" ? null : options == null ? void 0 : options.precision,
        offset: (_a = options == null ? void 0 : options.offset) != null ? _a : false,
        local: (_b = options == null ? void 0 : options.local) != null ? _b : false,
        ...errorUtil.errToObj(options == null ? void 0 : options.message)
      });
    }
    date(message) {
      return this._addCheck({ kind: "date", message });
    }
    time(options) {
      if (typeof options === "string") {
        return this._addCheck({
          kind: "time",
          precision: null,
          message: options
        });
      }
      return this._addCheck({
        kind: "time",
        precision: typeof (options == null ? void 0 : options.precision) === "undefined" ? null : options == null ? void 0 : options.precision,
        ...errorUtil.errToObj(options == null ? void 0 : options.message)
      });
    }
    duration(message) {
      return this._addCheck({ kind: "duration", ...errorUtil.errToObj(message) });
    }
    regex(regex, message) {
      return this._addCheck({
        kind: "regex",
        regex,
        ...errorUtil.errToObj(message)
      });
    }
    includes(value, options) {
      return this._addCheck({
        kind: "includes",
        value,
        position: options == null ? void 0 : options.position,
        ...errorUtil.errToObj(options == null ? void 0 : options.message)
      });
    }
    startsWith(value, message) {
      return this._addCheck({
        kind: "startsWith",
        value,
        ...errorUtil.errToObj(message)
      });
    }
    endsWith(value, message) {
      return this._addCheck({
        kind: "endsWith",
        value,
        ...errorUtil.errToObj(message)
      });
    }
    min(minLength, message) {
      return this._addCheck({
        kind: "min",
        value: minLength,
        ...errorUtil.errToObj(message)
      });
    }
    max(maxLength, message) {
      return this._addCheck({
        kind: "max",
        value: maxLength,
        ...errorUtil.errToObj(message)
      });
    }
    length(len, message) {
      return this._addCheck({
        kind: "length",
        value: len,
        ...errorUtil.errToObj(message)
      });
    }
    /**
     * Equivalent to `.min(1)`
     */
    nonempty(message) {
      return this.min(1, errorUtil.errToObj(message));
    }
    trim() {
      return new _ZodString({
        ...this._def,
        checks: [...this._def.checks, { kind: "trim" }]
      });
    }
    toLowerCase() {
      return new _ZodString({
        ...this._def,
        checks: [...this._def.checks, { kind: "toLowerCase" }]
      });
    }
    toUpperCase() {
      return new _ZodString({
        ...this._def,
        checks: [...this._def.checks, { kind: "toUpperCase" }]
      });
    }
    get isDatetime() {
      return !!this._def.checks.find((ch) => ch.kind === "datetime");
    }
    get isDate() {
      return !!this._def.checks.find((ch) => ch.kind === "date");
    }
    get isTime() {
      return !!this._def.checks.find((ch) => ch.kind === "time");
    }
    get isDuration() {
      return !!this._def.checks.find((ch) => ch.kind === "duration");
    }
    get isEmail() {
      return !!this._def.checks.find((ch) => ch.kind === "email");
    }
    get isURL() {
      return !!this._def.checks.find((ch) => ch.kind === "url");
    }
    get isEmoji() {
      return !!this._def.checks.find((ch) => ch.kind === "emoji");
    }
    get isUUID() {
      return !!this._def.checks.find((ch) => ch.kind === "uuid");
    }
    get isNANOID() {
      return !!this._def.checks.find((ch) => ch.kind === "nanoid");
    }
    get isCUID() {
      return !!this._def.checks.find((ch) => ch.kind === "cuid");
    }
    get isCUID2() {
      return !!this._def.checks.find((ch) => ch.kind === "cuid2");
    }
    get isULID() {
      return !!this._def.checks.find((ch) => ch.kind === "ulid");
    }
    get isIP() {
      return !!this._def.checks.find((ch) => ch.kind === "ip");
    }
    get isCIDR() {
      return !!this._def.checks.find((ch) => ch.kind === "cidr");
    }
    get isBase64() {
      return !!this._def.checks.find((ch) => ch.kind === "base64");
    }
    get isBase64url() {
      return !!this._def.checks.find((ch) => ch.kind === "base64url");
    }
    get minLength() {
      let min = null;
      for (const ch of this._def.checks) {
        if (ch.kind === "min") {
          if (min === null || ch.value > min)
            min = ch.value;
        }
      }
      return min;
    }
    get maxLength() {
      let max = null;
      for (const ch of this._def.checks) {
        if (ch.kind === "max") {
          if (max === null || ch.value < max)
            max = ch.value;
        }
      }
      return max;
    }
  };
  ZodString.create = (params) => {
    var _a;
    return new ZodString({
      checks: [],
      typeName: ZodFirstPartyTypeKind.ZodString,
      coerce: (_a = params == null ? void 0 : params.coerce) != null ? _a : false,
      ...processCreateParams(params)
    });
  };
  function floatSafeRemainder(val, step) {
    const valDecCount = (val.toString().split(".")[1] || "").length;
    const stepDecCount = (step.toString().split(".")[1] || "").length;
    const decCount = valDecCount > stepDecCount ? valDecCount : stepDecCount;
    const valInt = Number.parseInt(val.toFixed(decCount).replace(".", ""));
    const stepInt = Number.parseInt(step.toFixed(decCount).replace(".", ""));
    return valInt % stepInt / 10 ** decCount;
  }
  var ZodNumber = class _ZodNumber extends ZodType {
    constructor() {
      super(...arguments);
      this.min = this.gte;
      this.max = this.lte;
      this.step = this.multipleOf;
    }
    _parse(input) {
      if (this._def.coerce) {
        input.data = Number(input.data);
      }
      const parsedType = this._getType(input);
      if (parsedType !== ZodParsedType.number) {
        const ctx2 = this._getOrReturnCtx(input);
        addIssueToContext(ctx2, {
          code: ZodIssueCode.invalid_type,
          expected: ZodParsedType.number,
          received: ctx2.parsedType
        });
        return INVALID;
      }
      let ctx = void 0;
      const status = new ParseStatus();
      for (const check of this._def.checks) {
        if (check.kind === "int") {
          if (!util.isInteger(input.data)) {
            ctx = this._getOrReturnCtx(input, ctx);
            addIssueToContext(ctx, {
              code: ZodIssueCode.invalid_type,
              expected: "integer",
              received: "float",
              message: check.message
            });
            status.dirty();
          }
        } else if (check.kind === "min") {
          const tooSmall = check.inclusive ? input.data < check.value : input.data <= check.value;
          if (tooSmall) {
            ctx = this._getOrReturnCtx(input, ctx);
            addIssueToContext(ctx, {
              code: ZodIssueCode.too_small,
              minimum: check.value,
              type: "number",
              inclusive: check.inclusive,
              exact: false,
              message: check.message
            });
            status.dirty();
          }
        } else if (check.kind === "max") {
          const tooBig = check.inclusive ? input.data > check.value : input.data >= check.value;
          if (tooBig) {
            ctx = this._getOrReturnCtx(input, ctx);
            addIssueToContext(ctx, {
              code: ZodIssueCode.too_big,
              maximum: check.value,
              type: "number",
              inclusive: check.inclusive,
              exact: false,
              message: check.message
            });
            status.dirty();
          }
        } else if (check.kind === "multipleOf") {
          if (floatSafeRemainder(input.data, check.value) !== 0) {
            ctx = this._getOrReturnCtx(input, ctx);
            addIssueToContext(ctx, {
              code: ZodIssueCode.not_multiple_of,
              multipleOf: check.value,
              message: check.message
            });
            status.dirty();
          }
        } else if (check.kind === "finite") {
          if (!Number.isFinite(input.data)) {
            ctx = this._getOrReturnCtx(input, ctx);
            addIssueToContext(ctx, {
              code: ZodIssueCode.not_finite,
              message: check.message
            });
            status.dirty();
          }
        } else {
          util.assertNever(check);
        }
      }
      return { status: status.value, value: input.data };
    }
    gte(value, message) {
      return this.setLimit("min", value, true, errorUtil.toString(message));
    }
    gt(value, message) {
      return this.setLimit("min", value, false, errorUtil.toString(message));
    }
    lte(value, message) {
      return this.setLimit("max", value, true, errorUtil.toString(message));
    }
    lt(value, message) {
      return this.setLimit("max", value, false, errorUtil.toString(message));
    }
    setLimit(kind, value, inclusive, message) {
      return new _ZodNumber({
        ...this._def,
        checks: [
          ...this._def.checks,
          {
            kind,
            value,
            inclusive,
            message: errorUtil.toString(message)
          }
        ]
      });
    }
    _addCheck(check) {
      return new _ZodNumber({
        ...this._def,
        checks: [...this._def.checks, check]
      });
    }
    int(message) {
      return this._addCheck({
        kind: "int",
        message: errorUtil.toString(message)
      });
    }
    positive(message) {
      return this._addCheck({
        kind: "min",
        value: 0,
        inclusive: false,
        message: errorUtil.toString(message)
      });
    }
    negative(message) {
      return this._addCheck({
        kind: "max",
        value: 0,
        inclusive: false,
        message: errorUtil.toString(message)
      });
    }
    nonpositive(message) {
      return this._addCheck({
        kind: "max",
        value: 0,
        inclusive: true,
        message: errorUtil.toString(message)
      });
    }
    nonnegative(message) {
      return this._addCheck({
        kind: "min",
        value: 0,
        inclusive: true,
        message: errorUtil.toString(message)
      });
    }
    multipleOf(value, message) {
      return this._addCheck({
        kind: "multipleOf",
        value,
        message: errorUtil.toString(message)
      });
    }
    finite(message) {
      return this._addCheck({
        kind: "finite",
        message: errorUtil.toString(message)
      });
    }
    safe(message) {
      return this._addCheck({
        kind: "min",
        inclusive: true,
        value: Number.MIN_SAFE_INTEGER,
        message: errorUtil.toString(message)
      })._addCheck({
        kind: "max",
        inclusive: true,
        value: Number.MAX_SAFE_INTEGER,
        message: errorUtil.toString(message)
      });
    }
    get minValue() {
      let min = null;
      for (const ch of this._def.checks) {
        if (ch.kind === "min") {
          if (min === null || ch.value > min)
            min = ch.value;
        }
      }
      return min;
    }
    get maxValue() {
      let max = null;
      for (const ch of this._def.checks) {
        if (ch.kind === "max") {
          if (max === null || ch.value < max)
            max = ch.value;
        }
      }
      return max;
    }
    get isInt() {
      return !!this._def.checks.find((ch) => ch.kind === "int" || ch.kind === "multipleOf" && util.isInteger(ch.value));
    }
    get isFinite() {
      let max = null;
      let min = null;
      for (const ch of this._def.checks) {
        if (ch.kind === "finite" || ch.kind === "int" || ch.kind === "multipleOf") {
          return true;
        } else if (ch.kind === "min") {
          if (min === null || ch.value > min)
            min = ch.value;
        } else if (ch.kind === "max") {
          if (max === null || ch.value < max)
            max = ch.value;
        }
      }
      return Number.isFinite(min) && Number.isFinite(max);
    }
  };
  ZodNumber.create = (params) => {
    return new ZodNumber({
      checks: [],
      typeName: ZodFirstPartyTypeKind.ZodNumber,
      coerce: (params == null ? void 0 : params.coerce) || false,
      ...processCreateParams(params)
    });
  };
  var ZodBigInt = class _ZodBigInt extends ZodType {
    constructor() {
      super(...arguments);
      this.min = this.gte;
      this.max = this.lte;
    }
    _parse(input) {
      if (this._def.coerce) {
        try {
          input.data = BigInt(input.data);
        } catch {
          return this._getInvalidInput(input);
        }
      }
      const parsedType = this._getType(input);
      if (parsedType !== ZodParsedType.bigint) {
        return this._getInvalidInput(input);
      }
      let ctx = void 0;
      const status = new ParseStatus();
      for (const check of this._def.checks) {
        if (check.kind === "min") {
          const tooSmall = check.inclusive ? input.data < check.value : input.data <= check.value;
          if (tooSmall) {
            ctx = this._getOrReturnCtx(input, ctx);
            addIssueToContext(ctx, {
              code: ZodIssueCode.too_small,
              type: "bigint",
              minimum: check.value,
              inclusive: check.inclusive,
              message: check.message
            });
            status.dirty();
          }
        } else if (check.kind === "max") {
          const tooBig = check.inclusive ? input.data > check.value : input.data >= check.value;
          if (tooBig) {
            ctx = this._getOrReturnCtx(input, ctx);
            addIssueToContext(ctx, {
              code: ZodIssueCode.too_big,
              type: "bigint",
              maximum: check.value,
              inclusive: check.inclusive,
              message: check.message
            });
            status.dirty();
          }
        } else if (check.kind === "multipleOf") {
          if (input.data % check.value !== BigInt(0)) {
            ctx = this._getOrReturnCtx(input, ctx);
            addIssueToContext(ctx, {
              code: ZodIssueCode.not_multiple_of,
              multipleOf: check.value,
              message: check.message
            });
            status.dirty();
          }
        } else {
          util.assertNever(check);
        }
      }
      return { status: status.value, value: input.data };
    }
    _getInvalidInput(input) {
      const ctx = this._getOrReturnCtx(input);
      addIssueToContext(ctx, {
        code: ZodIssueCode.invalid_type,
        expected: ZodParsedType.bigint,
        received: ctx.parsedType
      });
      return INVALID;
    }
    gte(value, message) {
      return this.setLimit("min", value, true, errorUtil.toString(message));
    }
    gt(value, message) {
      return this.setLimit("min", value, false, errorUtil.toString(message));
    }
    lte(value, message) {
      return this.setLimit("max", value, true, errorUtil.toString(message));
    }
    lt(value, message) {
      return this.setLimit("max", value, false, errorUtil.toString(message));
    }
    setLimit(kind, value, inclusive, message) {
      return new _ZodBigInt({
        ...this._def,
        checks: [
          ...this._def.checks,
          {
            kind,
            value,
            inclusive,
            message: errorUtil.toString(message)
          }
        ]
      });
    }
    _addCheck(check) {
      return new _ZodBigInt({
        ...this._def,
        checks: [...this._def.checks, check]
      });
    }
    positive(message) {
      return this._addCheck({
        kind: "min",
        value: BigInt(0),
        inclusive: false,
        message: errorUtil.toString(message)
      });
    }
    negative(message) {
      return this._addCheck({
        kind: "max",
        value: BigInt(0),
        inclusive: false,
        message: errorUtil.toString(message)
      });
    }
    nonpositive(message) {
      return this._addCheck({
        kind: "max",
        value: BigInt(0),
        inclusive: true,
        message: errorUtil.toString(message)
      });
    }
    nonnegative(message) {
      return this._addCheck({
        kind: "min",
        value: BigInt(0),
        inclusive: true,
        message: errorUtil.toString(message)
      });
    }
    multipleOf(value, message) {
      return this._addCheck({
        kind: "multipleOf",
        value,
        message: errorUtil.toString(message)
      });
    }
    get minValue() {
      let min = null;
      for (const ch of this._def.checks) {
        if (ch.kind === "min") {
          if (min === null || ch.value > min)
            min = ch.value;
        }
      }
      return min;
    }
    get maxValue() {
      let max = null;
      for (const ch of this._def.checks) {
        if (ch.kind === "max") {
          if (max === null || ch.value < max)
            max = ch.value;
        }
      }
      return max;
    }
  };
  ZodBigInt.create = (params) => {
    var _a;
    return new ZodBigInt({
      checks: [],
      typeName: ZodFirstPartyTypeKind.ZodBigInt,
      coerce: (_a = params == null ? void 0 : params.coerce) != null ? _a : false,
      ...processCreateParams(params)
    });
  };
  var ZodBoolean = class extends ZodType {
    _parse(input) {
      if (this._def.coerce) {
        input.data = Boolean(input.data);
      }
      const parsedType = this._getType(input);
      if (parsedType !== ZodParsedType.boolean) {
        const ctx = this._getOrReturnCtx(input);
        addIssueToContext(ctx, {
          code: ZodIssueCode.invalid_type,
          expected: ZodParsedType.boolean,
          received: ctx.parsedType
        });
        return INVALID;
      }
      return OK(input.data);
    }
  };
  ZodBoolean.create = (params) => {
    return new ZodBoolean({
      typeName: ZodFirstPartyTypeKind.ZodBoolean,
      coerce: (params == null ? void 0 : params.coerce) || false,
      ...processCreateParams(params)
    });
  };
  var ZodDate = class _ZodDate extends ZodType {
    _parse(input) {
      if (this._def.coerce) {
        input.data = new Date(input.data);
      }
      const parsedType = this._getType(input);
      if (parsedType !== ZodParsedType.date) {
        const ctx2 = this._getOrReturnCtx(input);
        addIssueToContext(ctx2, {
          code: ZodIssueCode.invalid_type,
          expected: ZodParsedType.date,
          received: ctx2.parsedType
        });
        return INVALID;
      }
      if (Number.isNaN(input.data.getTime())) {
        const ctx2 = this._getOrReturnCtx(input);
        addIssueToContext(ctx2, {
          code: ZodIssueCode.invalid_date
        });
        return INVALID;
      }
      const status = new ParseStatus();
      let ctx = void 0;
      for (const check of this._def.checks) {
        if (check.kind === "min") {
          if (input.data.getTime() < check.value) {
            ctx = this._getOrReturnCtx(input, ctx);
            addIssueToContext(ctx, {
              code: ZodIssueCode.too_small,
              message: check.message,
              inclusive: true,
              exact: false,
              minimum: check.value,
              type: "date"
            });
            status.dirty();
          }
        } else if (check.kind === "max") {
          if (input.data.getTime() > check.value) {
            ctx = this._getOrReturnCtx(input, ctx);
            addIssueToContext(ctx, {
              code: ZodIssueCode.too_big,
              message: check.message,
              inclusive: true,
              exact: false,
              maximum: check.value,
              type: "date"
            });
            status.dirty();
          }
        } else {
          util.assertNever(check);
        }
      }
      return {
        status: status.value,
        value: new Date(input.data.getTime())
      };
    }
    _addCheck(check) {
      return new _ZodDate({
        ...this._def,
        checks: [...this._def.checks, check]
      });
    }
    min(minDate, message) {
      return this._addCheck({
        kind: "min",
        value: minDate.getTime(),
        message: errorUtil.toString(message)
      });
    }
    max(maxDate, message) {
      return this._addCheck({
        kind: "max",
        value: maxDate.getTime(),
        message: errorUtil.toString(message)
      });
    }
    get minDate() {
      let min = null;
      for (const ch of this._def.checks) {
        if (ch.kind === "min") {
          if (min === null || ch.value > min)
            min = ch.value;
        }
      }
      return min != null ? new Date(min) : null;
    }
    get maxDate() {
      let max = null;
      for (const ch of this._def.checks) {
        if (ch.kind === "max") {
          if (max === null || ch.value < max)
            max = ch.value;
        }
      }
      return max != null ? new Date(max) : null;
    }
  };
  ZodDate.create = (params) => {
    return new ZodDate({
      checks: [],
      coerce: (params == null ? void 0 : params.coerce) || false,
      typeName: ZodFirstPartyTypeKind.ZodDate,
      ...processCreateParams(params)
    });
  };
  var ZodSymbol = class extends ZodType {
    _parse(input) {
      const parsedType = this._getType(input);
      if (parsedType !== ZodParsedType.symbol) {
        const ctx = this._getOrReturnCtx(input);
        addIssueToContext(ctx, {
          code: ZodIssueCode.invalid_type,
          expected: ZodParsedType.symbol,
          received: ctx.parsedType
        });
        return INVALID;
      }
      return OK(input.data);
    }
  };
  ZodSymbol.create = (params) => {
    return new ZodSymbol({
      typeName: ZodFirstPartyTypeKind.ZodSymbol,
      ...processCreateParams(params)
    });
  };
  var ZodUndefined = class extends ZodType {
    _parse(input) {
      const parsedType = this._getType(input);
      if (parsedType !== ZodParsedType.undefined) {
        const ctx = this._getOrReturnCtx(input);
        addIssueToContext(ctx, {
          code: ZodIssueCode.invalid_type,
          expected: ZodParsedType.undefined,
          received: ctx.parsedType
        });
        return INVALID;
      }
      return OK(input.data);
    }
  };
  ZodUndefined.create = (params) => {
    return new ZodUndefined({
      typeName: ZodFirstPartyTypeKind.ZodUndefined,
      ...processCreateParams(params)
    });
  };
  var ZodNull = class extends ZodType {
    _parse(input) {
      const parsedType = this._getType(input);
      if (parsedType !== ZodParsedType.null) {
        const ctx = this._getOrReturnCtx(input);
        addIssueToContext(ctx, {
          code: ZodIssueCode.invalid_type,
          expected: ZodParsedType.null,
          received: ctx.parsedType
        });
        return INVALID;
      }
      return OK(input.data);
    }
  };
  ZodNull.create = (params) => {
    return new ZodNull({
      typeName: ZodFirstPartyTypeKind.ZodNull,
      ...processCreateParams(params)
    });
  };
  var ZodAny = class extends ZodType {
    constructor() {
      super(...arguments);
      this._any = true;
    }
    _parse(input) {
      return OK(input.data);
    }
  };
  ZodAny.create = (params) => {
    return new ZodAny({
      typeName: ZodFirstPartyTypeKind.ZodAny,
      ...processCreateParams(params)
    });
  };
  var ZodUnknown = class extends ZodType {
    constructor() {
      super(...arguments);
      this._unknown = true;
    }
    _parse(input) {
      return OK(input.data);
    }
  };
  ZodUnknown.create = (params) => {
    return new ZodUnknown({
      typeName: ZodFirstPartyTypeKind.ZodUnknown,
      ...processCreateParams(params)
    });
  };
  var ZodNever = class extends ZodType {
    _parse(input) {
      const ctx = this._getOrReturnCtx(input);
      addIssueToContext(ctx, {
        code: ZodIssueCode.invalid_type,
        expected: ZodParsedType.never,
        received: ctx.parsedType
      });
      return INVALID;
    }
  };
  ZodNever.create = (params) => {
    return new ZodNever({
      typeName: ZodFirstPartyTypeKind.ZodNever,
      ...processCreateParams(params)
    });
  };
  var ZodVoid = class extends ZodType {
    _parse(input) {
      const parsedType = this._getType(input);
      if (parsedType !== ZodParsedType.undefined) {
        const ctx = this._getOrReturnCtx(input);
        addIssueToContext(ctx, {
          code: ZodIssueCode.invalid_type,
          expected: ZodParsedType.void,
          received: ctx.parsedType
        });
        return INVALID;
      }
      return OK(input.data);
    }
  };
  ZodVoid.create = (params) => {
    return new ZodVoid({
      typeName: ZodFirstPartyTypeKind.ZodVoid,
      ...processCreateParams(params)
    });
  };
  var ZodArray = class _ZodArray extends ZodType {
    _parse(input) {
      const { ctx, status } = this._processInputParams(input);
      const def = this._def;
      if (ctx.parsedType !== ZodParsedType.array) {
        addIssueToContext(ctx, {
          code: ZodIssueCode.invalid_type,
          expected: ZodParsedType.array,
          received: ctx.parsedType
        });
        return INVALID;
      }
      if (def.exactLength !== null) {
        const tooBig = ctx.data.length > def.exactLength.value;
        const tooSmall = ctx.data.length < def.exactLength.value;
        if (tooBig || tooSmall) {
          addIssueToContext(ctx, {
            code: tooBig ? ZodIssueCode.too_big : ZodIssueCode.too_small,
            minimum: tooSmall ? def.exactLength.value : void 0,
            maximum: tooBig ? def.exactLength.value : void 0,
            type: "array",
            inclusive: true,
            exact: true,
            message: def.exactLength.message
          });
          status.dirty();
        }
      }
      if (def.minLength !== null) {
        if (ctx.data.length < def.minLength.value) {
          addIssueToContext(ctx, {
            code: ZodIssueCode.too_small,
            minimum: def.minLength.value,
            type: "array",
            inclusive: true,
            exact: false,
            message: def.minLength.message
          });
          status.dirty();
        }
      }
      if (def.maxLength !== null) {
        if (ctx.data.length > def.maxLength.value) {
          addIssueToContext(ctx, {
            code: ZodIssueCode.too_big,
            maximum: def.maxLength.value,
            type: "array",
            inclusive: true,
            exact: false,
            message: def.maxLength.message
          });
          status.dirty();
        }
      }
      if (ctx.common.async) {
        return Promise.all([...ctx.data].map((item, i) => {
          return def.type._parseAsync(new ParseInputLazyPath(ctx, item, ctx.path, i));
        })).then((result2) => {
          return ParseStatus.mergeArray(status, result2);
        });
      }
      const result = [...ctx.data].map((item, i) => {
        return def.type._parseSync(new ParseInputLazyPath(ctx, item, ctx.path, i));
      });
      return ParseStatus.mergeArray(status, result);
    }
    get element() {
      return this._def.type;
    }
    min(minLength, message) {
      return new _ZodArray({
        ...this._def,
        minLength: { value: minLength, message: errorUtil.toString(message) }
      });
    }
    max(maxLength, message) {
      return new _ZodArray({
        ...this._def,
        maxLength: { value: maxLength, message: errorUtil.toString(message) }
      });
    }
    length(len, message) {
      return new _ZodArray({
        ...this._def,
        exactLength: { value: len, message: errorUtil.toString(message) }
      });
    }
    nonempty(message) {
      return this.min(1, message);
    }
  };
  ZodArray.create = (schema, params) => {
    return new ZodArray({
      type: schema,
      minLength: null,
      maxLength: null,
      exactLength: null,
      typeName: ZodFirstPartyTypeKind.ZodArray,
      ...processCreateParams(params)
    });
  };
  function deepPartialify(schema) {
    if (schema instanceof ZodObject) {
      const newShape = {};
      for (const key in schema.shape) {
        const fieldSchema = schema.shape[key];
        newShape[key] = ZodOptional.create(deepPartialify(fieldSchema));
      }
      return new ZodObject({
        ...schema._def,
        shape: () => newShape
      });
    } else if (schema instanceof ZodArray) {
      return new ZodArray({
        ...schema._def,
        type: deepPartialify(schema.element)
      });
    } else if (schema instanceof ZodOptional) {
      return ZodOptional.create(deepPartialify(schema.unwrap()));
    } else if (schema instanceof ZodNullable) {
      return ZodNullable.create(deepPartialify(schema.unwrap()));
    } else if (schema instanceof ZodTuple) {
      return ZodTuple.create(schema.items.map((item) => deepPartialify(item)));
    } else {
      return schema;
    }
  }
  var ZodObject = class _ZodObject extends ZodType {
    constructor() {
      super(...arguments);
      this._cached = null;
      this.nonstrict = this.passthrough;
      this.augment = this.extend;
    }
    _getCached() {
      if (this._cached !== null)
        return this._cached;
      const shape = this._def.shape();
      const keys = util.objectKeys(shape);
      this._cached = { shape, keys };
      return this._cached;
    }
    _parse(input) {
      const parsedType = this._getType(input);
      if (parsedType !== ZodParsedType.object) {
        const ctx2 = this._getOrReturnCtx(input);
        addIssueToContext(ctx2, {
          code: ZodIssueCode.invalid_type,
          expected: ZodParsedType.object,
          received: ctx2.parsedType
        });
        return INVALID;
      }
      const { status, ctx } = this._processInputParams(input);
      const { shape, keys: shapeKeys } = this._getCached();
      const extraKeys = [];
      if (!(this._def.catchall instanceof ZodNever && this._def.unknownKeys === "strip")) {
        for (const key in ctx.data) {
          if (!shapeKeys.includes(key)) {
            extraKeys.push(key);
          }
        }
      }
      const pairs = [];
      for (const key of shapeKeys) {
        const keyValidator = shape[key];
        const value = ctx.data[key];
        pairs.push({
          key: { status: "valid", value: key },
          value: keyValidator._parse(new ParseInputLazyPath(ctx, value, ctx.path, key)),
          alwaysSet: key in ctx.data
        });
      }
      if (this._def.catchall instanceof ZodNever) {
        const unknownKeys = this._def.unknownKeys;
        if (unknownKeys === "passthrough") {
          for (const key of extraKeys) {
            pairs.push({
              key: { status: "valid", value: key },
              value: { status: "valid", value: ctx.data[key] }
            });
          }
        } else if (unknownKeys === "strict") {
          if (extraKeys.length > 0) {
            addIssueToContext(ctx, {
              code: ZodIssueCode.unrecognized_keys,
              keys: extraKeys
            });
            status.dirty();
          }
        } else if (unknownKeys === "strip") {
        } else {
          throw new Error(`Internal ZodObject error: invalid unknownKeys value.`);
        }
      } else {
        const catchall = this._def.catchall;
        for (const key of extraKeys) {
          const value = ctx.data[key];
          pairs.push({
            key: { status: "valid", value: key },
            value: catchall._parse(
              new ParseInputLazyPath(ctx, value, ctx.path, key)
              //, ctx.child(key), value, getParsedType(value)
            ),
            alwaysSet: key in ctx.data
          });
        }
      }
      if (ctx.common.async) {
        return Promise.resolve().then(async () => {
          const syncPairs = [];
          for (const pair of pairs) {
            const key = await pair.key;
            const value = await pair.value;
            syncPairs.push({
              key,
              value,
              alwaysSet: pair.alwaysSet
            });
          }
          return syncPairs;
        }).then((syncPairs) => {
          return ParseStatus.mergeObjectSync(status, syncPairs);
        });
      } else {
        return ParseStatus.mergeObjectSync(status, pairs);
      }
    }
    get shape() {
      return this._def.shape();
    }
    strict(message) {
      errorUtil.errToObj;
      return new _ZodObject({
        ...this._def,
        unknownKeys: "strict",
        ...message !== void 0 ? {
          errorMap: (issue, ctx) => {
            var _a, _b, _c, _d;
            const defaultError = (_c = (_b = (_a = this._def).errorMap) == null ? void 0 : _b.call(_a, issue, ctx).message) != null ? _c : ctx.defaultError;
            if (issue.code === "unrecognized_keys")
              return {
                message: (_d = errorUtil.errToObj(message).message) != null ? _d : defaultError
              };
            return {
              message: defaultError
            };
          }
        } : {}
      });
    }
    strip() {
      return new _ZodObject({
        ...this._def,
        unknownKeys: "strip"
      });
    }
    passthrough() {
      return new _ZodObject({
        ...this._def,
        unknownKeys: "passthrough"
      });
    }
    // const AugmentFactory =
    //   <Def extends ZodObjectDef>(def: Def) =>
    //   <Augmentation extends ZodRawShape>(
    //     augmentation: Augmentation
    //   ): ZodObject<
    //     extendShape<ReturnType<Def["shape"]>, Augmentation>,
    //     Def["unknownKeys"],
    //     Def["catchall"]
    //   > => {
    //     return new ZodObject({
    //       ...def,
    //       shape: () => ({
    //         ...def.shape(),
    //         ...augmentation,
    //       }),
    //     }) as any;
    //   };
    extend(augmentation) {
      return new _ZodObject({
        ...this._def,
        shape: () => ({
          ...this._def.shape(),
          ...augmentation
        })
      });
    }
    /**
     * Prior to zod@1.0.12 there was a bug in the
     * inferred type of merged objects. Please
     * upgrade if you are experiencing issues.
     */
    merge(merging) {
      const merged = new _ZodObject({
        unknownKeys: merging._def.unknownKeys,
        catchall: merging._def.catchall,
        shape: () => ({
          ...this._def.shape(),
          ...merging._def.shape()
        }),
        typeName: ZodFirstPartyTypeKind.ZodObject
      });
      return merged;
    }
    // merge<
    //   Incoming extends AnyZodObject,
    //   Augmentation extends Incoming["shape"],
    //   NewOutput extends {
    //     [k in keyof Augmentation | keyof Output]: k extends keyof Augmentation
    //       ? Augmentation[k]["_output"]
    //       : k extends keyof Output
    //       ? Output[k]
    //       : never;
    //   },
    //   NewInput extends {
    //     [k in keyof Augmentation | keyof Input]: k extends keyof Augmentation
    //       ? Augmentation[k]["_input"]
    //       : k extends keyof Input
    //       ? Input[k]
    //       : never;
    //   }
    // >(
    //   merging: Incoming
    // ): ZodObject<
    //   extendShape<T, ReturnType<Incoming["_def"]["shape"]>>,
    //   Incoming["_def"]["unknownKeys"],
    //   Incoming["_def"]["catchall"],
    //   NewOutput,
    //   NewInput
    // > {
    //   const merged: any = new ZodObject({
    //     unknownKeys: merging._def.unknownKeys,
    //     catchall: merging._def.catchall,
    //     shape: () =>
    //       objectUtil.mergeShapes(this._def.shape(), merging._def.shape()),
    //     typeName: ZodFirstPartyTypeKind.ZodObject,
    //   }) as any;
    //   return merged;
    // }
    setKey(key, schema) {
      return this.augment({ [key]: schema });
    }
    // merge<Incoming extends AnyZodObject>(
    //   merging: Incoming
    // ): //ZodObject<T & Incoming["_shape"], UnknownKeys, Catchall> = (merging) => {
    // ZodObject<
    //   extendShape<T, ReturnType<Incoming["_def"]["shape"]>>,
    //   Incoming["_def"]["unknownKeys"],
    //   Incoming["_def"]["catchall"]
    // > {
    //   // const mergedShape = objectUtil.mergeShapes(
    //   //   this._def.shape(),
    //   //   merging._def.shape()
    //   // );
    //   const merged: any = new ZodObject({
    //     unknownKeys: merging._def.unknownKeys,
    //     catchall: merging._def.catchall,
    //     shape: () =>
    //       objectUtil.mergeShapes(this._def.shape(), merging._def.shape()),
    //     typeName: ZodFirstPartyTypeKind.ZodObject,
    //   }) as any;
    //   return merged;
    // }
    catchall(index) {
      return new _ZodObject({
        ...this._def,
        catchall: index
      });
    }
    pick(mask) {
      const shape = {};
      for (const key of util.objectKeys(mask)) {
        if (mask[key] && this.shape[key]) {
          shape[key] = this.shape[key];
        }
      }
      return new _ZodObject({
        ...this._def,
        shape: () => shape
      });
    }
    omit(mask) {
      const shape = {};
      for (const key of util.objectKeys(this.shape)) {
        if (!mask[key]) {
          shape[key] = this.shape[key];
        }
      }
      return new _ZodObject({
        ...this._def,
        shape: () => shape
      });
    }
    /**
     * @deprecated
     */
    deepPartial() {
      return deepPartialify(this);
    }
    partial(mask) {
      const newShape = {};
      for (const key of util.objectKeys(this.shape)) {
        const fieldSchema = this.shape[key];
        if (mask && !mask[key]) {
          newShape[key] = fieldSchema;
        } else {
          newShape[key] = fieldSchema.optional();
        }
      }
      return new _ZodObject({
        ...this._def,
        shape: () => newShape
      });
    }
    required(mask) {
      const newShape = {};
      for (const key of util.objectKeys(this.shape)) {
        if (mask && !mask[key]) {
          newShape[key] = this.shape[key];
        } else {
          const fieldSchema = this.shape[key];
          let newField = fieldSchema;
          while (newField instanceof ZodOptional) {
            newField = newField._def.innerType;
          }
          newShape[key] = newField;
        }
      }
      return new _ZodObject({
        ...this._def,
        shape: () => newShape
      });
    }
    keyof() {
      return createZodEnum(util.objectKeys(this.shape));
    }
  };
  ZodObject.create = (shape, params) => {
    return new ZodObject({
      shape: () => shape,
      unknownKeys: "strip",
      catchall: ZodNever.create(),
      typeName: ZodFirstPartyTypeKind.ZodObject,
      ...processCreateParams(params)
    });
  };
  ZodObject.strictCreate = (shape, params) => {
    return new ZodObject({
      shape: () => shape,
      unknownKeys: "strict",
      catchall: ZodNever.create(),
      typeName: ZodFirstPartyTypeKind.ZodObject,
      ...processCreateParams(params)
    });
  };
  ZodObject.lazycreate = (shape, params) => {
    return new ZodObject({
      shape,
      unknownKeys: "strip",
      catchall: ZodNever.create(),
      typeName: ZodFirstPartyTypeKind.ZodObject,
      ...processCreateParams(params)
    });
  };
  var ZodUnion = class extends ZodType {
    _parse(input) {
      const { ctx } = this._processInputParams(input);
      const options = this._def.options;
      function handleResults(results) {
        for (const result of results) {
          if (result.result.status === "valid") {
            return result.result;
          }
        }
        for (const result of results) {
          if (result.result.status === "dirty") {
            ctx.common.issues.push(...result.ctx.common.issues);
            return result.result;
          }
        }
        const unionErrors = results.map((result) => new ZodError(result.ctx.common.issues));
        addIssueToContext(ctx, {
          code: ZodIssueCode.invalid_union,
          unionErrors
        });
        return INVALID;
      }
      if (ctx.common.async) {
        return Promise.all(options.map(async (option) => {
          const childCtx = {
            ...ctx,
            common: {
              ...ctx.common,
              issues: []
            },
            parent: null
          };
          return {
            result: await option._parseAsync({
              data: ctx.data,
              path: ctx.path,
              parent: childCtx
            }),
            ctx: childCtx
          };
        })).then(handleResults);
      } else {
        let dirty = void 0;
        const issues = [];
        for (const option of options) {
          const childCtx = {
            ...ctx,
            common: {
              ...ctx.common,
              issues: []
            },
            parent: null
          };
          const result = option._parseSync({
            data: ctx.data,
            path: ctx.path,
            parent: childCtx
          });
          if (result.status === "valid") {
            return result;
          } else if (result.status === "dirty" && !dirty) {
            dirty = { result, ctx: childCtx };
          }
          if (childCtx.common.issues.length) {
            issues.push(childCtx.common.issues);
          }
        }
        if (dirty) {
          ctx.common.issues.push(...dirty.ctx.common.issues);
          return dirty.result;
        }
        const unionErrors = issues.map((issues2) => new ZodError(issues2));
        addIssueToContext(ctx, {
          code: ZodIssueCode.invalid_union,
          unionErrors
        });
        return INVALID;
      }
    }
    get options() {
      return this._def.options;
    }
  };
  ZodUnion.create = (types, params) => {
    return new ZodUnion({
      options: types,
      typeName: ZodFirstPartyTypeKind.ZodUnion,
      ...processCreateParams(params)
    });
  };
  var getDiscriminator = (type) => {
    if (type instanceof ZodLazy) {
      return getDiscriminator(type.schema);
    } else if (type instanceof ZodEffects) {
      return getDiscriminator(type.innerType());
    } else if (type instanceof ZodLiteral) {
      return [type.value];
    } else if (type instanceof ZodEnum) {
      return type.options;
    } else if (type instanceof ZodNativeEnum) {
      return util.objectValues(type.enum);
    } else if (type instanceof ZodDefault) {
      return getDiscriminator(type._def.innerType);
    } else if (type instanceof ZodUndefined) {
      return [void 0];
    } else if (type instanceof ZodNull) {
      return [null];
    } else if (type instanceof ZodOptional) {
      return [void 0, ...getDiscriminator(type.unwrap())];
    } else if (type instanceof ZodNullable) {
      return [null, ...getDiscriminator(type.unwrap())];
    } else if (type instanceof ZodBranded) {
      return getDiscriminator(type.unwrap());
    } else if (type instanceof ZodReadonly) {
      return getDiscriminator(type.unwrap());
    } else if (type instanceof ZodCatch) {
      return getDiscriminator(type._def.innerType);
    } else {
      return [];
    }
  };
  var ZodDiscriminatedUnion = class _ZodDiscriminatedUnion extends ZodType {
    _parse(input) {
      const { ctx } = this._processInputParams(input);
      if (ctx.parsedType !== ZodParsedType.object) {
        addIssueToContext(ctx, {
          code: ZodIssueCode.invalid_type,
          expected: ZodParsedType.object,
          received: ctx.parsedType
        });
        return INVALID;
      }
      const discriminator = this.discriminator;
      const discriminatorValue = ctx.data[discriminator];
      const option = this.optionsMap.get(discriminatorValue);
      if (!option) {
        addIssueToContext(ctx, {
          code: ZodIssueCode.invalid_union_discriminator,
          options: Array.from(this.optionsMap.keys()),
          path: [discriminator]
        });
        return INVALID;
      }
      if (ctx.common.async) {
        return option._parseAsync({
          data: ctx.data,
          path: ctx.path,
          parent: ctx
        });
      } else {
        return option._parseSync({
          data: ctx.data,
          path: ctx.path,
          parent: ctx
        });
      }
    }
    get discriminator() {
      return this._def.discriminator;
    }
    get options() {
      return this._def.options;
    }
    get optionsMap() {
      return this._def.optionsMap;
    }
    /**
     * The constructor of the discriminated union schema. Its behaviour is very similar to that of the normal z.union() constructor.
     * However, it only allows a union of objects, all of which need to share a discriminator property. This property must
     * have a different value for each object in the union.
     * @param discriminator the name of the discriminator property
     * @param types an array of object schemas
     * @param params
     */
    static create(discriminator, options, params) {
      const optionsMap = /* @__PURE__ */ new Map();
      for (const type of options) {
        const discriminatorValues = getDiscriminator(type.shape[discriminator]);
        if (!discriminatorValues.length) {
          throw new Error(`A discriminator value for key \`${discriminator}\` could not be extracted from all schema options`);
        }
        for (const value of discriminatorValues) {
          if (optionsMap.has(value)) {
            throw new Error(`Discriminator property ${String(discriminator)} has duplicate value ${String(value)}`);
          }
          optionsMap.set(value, type);
        }
      }
      return new _ZodDiscriminatedUnion({
        typeName: ZodFirstPartyTypeKind.ZodDiscriminatedUnion,
        discriminator,
        options,
        optionsMap,
        ...processCreateParams(params)
      });
    }
  };
  function mergeValues(a, b) {
    const aType = getParsedType(a);
    const bType = getParsedType(b);
    if (a === b) {
      return { valid: true, data: a };
    } else if (aType === ZodParsedType.object && bType === ZodParsedType.object) {
      const bKeys = util.objectKeys(b);
      const sharedKeys = util.objectKeys(a).filter((key) => bKeys.indexOf(key) !== -1);
      const newObj = { ...a, ...b };
      for (const key of sharedKeys) {
        const sharedValue = mergeValues(a[key], b[key]);
        if (!sharedValue.valid) {
          return { valid: false };
        }
        newObj[key] = sharedValue.data;
      }
      return { valid: true, data: newObj };
    } else if (aType === ZodParsedType.array && bType === ZodParsedType.array) {
      if (a.length !== b.length) {
        return { valid: false };
      }
      const newArray = [];
      for (let index = 0; index < a.length; index++) {
        const itemA = a[index];
        const itemB = b[index];
        const sharedValue = mergeValues(itemA, itemB);
        if (!sharedValue.valid) {
          return { valid: false };
        }
        newArray.push(sharedValue.data);
      }
      return { valid: true, data: newArray };
    } else if (aType === ZodParsedType.date && bType === ZodParsedType.date && +a === +b) {
      return { valid: true, data: a };
    } else {
      return { valid: false };
    }
  }
  var ZodIntersection = class extends ZodType {
    _parse(input) {
      const { status, ctx } = this._processInputParams(input);
      const handleParsed = (parsedLeft, parsedRight) => {
        if (isAborted(parsedLeft) || isAborted(parsedRight)) {
          return INVALID;
        }
        const merged = mergeValues(parsedLeft.value, parsedRight.value);
        if (!merged.valid) {
          addIssueToContext(ctx, {
            code: ZodIssueCode.invalid_intersection_types
          });
          return INVALID;
        }
        if (isDirty(parsedLeft) || isDirty(parsedRight)) {
          status.dirty();
        }
        return { status: status.value, value: merged.data };
      };
      if (ctx.common.async) {
        return Promise.all([
          this._def.left._parseAsync({
            data: ctx.data,
            path: ctx.path,
            parent: ctx
          }),
          this._def.right._parseAsync({
            data: ctx.data,
            path: ctx.path,
            parent: ctx
          })
        ]).then(([left, right]) => handleParsed(left, right));
      } else {
        return handleParsed(this._def.left._parseSync({
          data: ctx.data,
          path: ctx.path,
          parent: ctx
        }), this._def.right._parseSync({
          data: ctx.data,
          path: ctx.path,
          parent: ctx
        }));
      }
    }
  };
  ZodIntersection.create = (left, right, params) => {
    return new ZodIntersection({
      left,
      right,
      typeName: ZodFirstPartyTypeKind.ZodIntersection,
      ...processCreateParams(params)
    });
  };
  var ZodTuple = class _ZodTuple extends ZodType {
    _parse(input) {
      const { status, ctx } = this._processInputParams(input);
      if (ctx.parsedType !== ZodParsedType.array) {
        addIssueToContext(ctx, {
          code: ZodIssueCode.invalid_type,
          expected: ZodParsedType.array,
          received: ctx.parsedType
        });
        return INVALID;
      }
      if (ctx.data.length < this._def.items.length) {
        addIssueToContext(ctx, {
          code: ZodIssueCode.too_small,
          minimum: this._def.items.length,
          inclusive: true,
          exact: false,
          type: "array"
        });
        return INVALID;
      }
      const rest = this._def.rest;
      if (!rest && ctx.data.length > this._def.items.length) {
        addIssueToContext(ctx, {
          code: ZodIssueCode.too_big,
          maximum: this._def.items.length,
          inclusive: true,
          exact: false,
          type: "array"
        });
        status.dirty();
      }
      const items = [...ctx.data].map((item, itemIndex) => {
        const schema = this._def.items[itemIndex] || this._def.rest;
        if (!schema)
          return null;
        return schema._parse(new ParseInputLazyPath(ctx, item, ctx.path, itemIndex));
      }).filter((x) => !!x);
      if (ctx.common.async) {
        return Promise.all(items).then((results) => {
          return ParseStatus.mergeArray(status, results);
        });
      } else {
        return ParseStatus.mergeArray(status, items);
      }
    }
    get items() {
      return this._def.items;
    }
    rest(rest) {
      return new _ZodTuple({
        ...this._def,
        rest
      });
    }
  };
  ZodTuple.create = (schemas, params) => {
    if (!Array.isArray(schemas)) {
      throw new Error("You must pass an array of schemas to z.tuple([ ... ])");
    }
    return new ZodTuple({
      items: schemas,
      typeName: ZodFirstPartyTypeKind.ZodTuple,
      rest: null,
      ...processCreateParams(params)
    });
  };
  var ZodRecord = class _ZodRecord extends ZodType {
    get keySchema() {
      return this._def.keyType;
    }
    get valueSchema() {
      return this._def.valueType;
    }
    _parse(input) {
      const { status, ctx } = this._processInputParams(input);
      if (ctx.parsedType !== ZodParsedType.object) {
        addIssueToContext(ctx, {
          code: ZodIssueCode.invalid_type,
          expected: ZodParsedType.object,
          received: ctx.parsedType
        });
        return INVALID;
      }
      const pairs = [];
      const keyType = this._def.keyType;
      const valueType = this._def.valueType;
      for (const key in ctx.data) {
        pairs.push({
          key: keyType._parse(new ParseInputLazyPath(ctx, key, ctx.path, key)),
          value: valueType._parse(new ParseInputLazyPath(ctx, ctx.data[key], ctx.path, key)),
          alwaysSet: key in ctx.data
        });
      }
      if (ctx.common.async) {
        return ParseStatus.mergeObjectAsync(status, pairs);
      } else {
        return ParseStatus.mergeObjectSync(status, pairs);
      }
    }
    get element() {
      return this._def.valueType;
    }
    static create(first, second, third) {
      if (second instanceof ZodType) {
        return new _ZodRecord({
          keyType: first,
          valueType: second,
          typeName: ZodFirstPartyTypeKind.ZodRecord,
          ...processCreateParams(third)
        });
      }
      return new _ZodRecord({
        keyType: ZodString.create(),
        valueType: first,
        typeName: ZodFirstPartyTypeKind.ZodRecord,
        ...processCreateParams(second)
      });
    }
  };
  var ZodMap = class extends ZodType {
    get keySchema() {
      return this._def.keyType;
    }
    get valueSchema() {
      return this._def.valueType;
    }
    _parse(input) {
      const { status, ctx } = this._processInputParams(input);
      if (ctx.parsedType !== ZodParsedType.map) {
        addIssueToContext(ctx, {
          code: ZodIssueCode.invalid_type,
          expected: ZodParsedType.map,
          received: ctx.parsedType
        });
        return INVALID;
      }
      const keyType = this._def.keyType;
      const valueType = this._def.valueType;
      const pairs = [...ctx.data.entries()].map(([key, value], index) => {
        return {
          key: keyType._parse(new ParseInputLazyPath(ctx, key, ctx.path, [index, "key"])),
          value: valueType._parse(new ParseInputLazyPath(ctx, value, ctx.path, [index, "value"]))
        };
      });
      if (ctx.common.async) {
        const finalMap = /* @__PURE__ */ new Map();
        return Promise.resolve().then(async () => {
          for (const pair of pairs) {
            const key = await pair.key;
            const value = await pair.value;
            if (key.status === "aborted" || value.status === "aborted") {
              return INVALID;
            }
            if (key.status === "dirty" || value.status === "dirty") {
              status.dirty();
            }
            finalMap.set(key.value, value.value);
          }
          return { status: status.value, value: finalMap };
        });
      } else {
        const finalMap = /* @__PURE__ */ new Map();
        for (const pair of pairs) {
          const key = pair.key;
          const value = pair.value;
          if (key.status === "aborted" || value.status === "aborted") {
            return INVALID;
          }
          if (key.status === "dirty" || value.status === "dirty") {
            status.dirty();
          }
          finalMap.set(key.value, value.value);
        }
        return { status: status.value, value: finalMap };
      }
    }
  };
  ZodMap.create = (keyType, valueType, params) => {
    return new ZodMap({
      valueType,
      keyType,
      typeName: ZodFirstPartyTypeKind.ZodMap,
      ...processCreateParams(params)
    });
  };
  var ZodSet = class _ZodSet extends ZodType {
    _parse(input) {
      const { status, ctx } = this._processInputParams(input);
      if (ctx.parsedType !== ZodParsedType.set) {
        addIssueToContext(ctx, {
          code: ZodIssueCode.invalid_type,
          expected: ZodParsedType.set,
          received: ctx.parsedType
        });
        return INVALID;
      }
      const def = this._def;
      if (def.minSize !== null) {
        if (ctx.data.size < def.minSize.value) {
          addIssueToContext(ctx, {
            code: ZodIssueCode.too_small,
            minimum: def.minSize.value,
            type: "set",
            inclusive: true,
            exact: false,
            message: def.minSize.message
          });
          status.dirty();
        }
      }
      if (def.maxSize !== null) {
        if (ctx.data.size > def.maxSize.value) {
          addIssueToContext(ctx, {
            code: ZodIssueCode.too_big,
            maximum: def.maxSize.value,
            type: "set",
            inclusive: true,
            exact: false,
            message: def.maxSize.message
          });
          status.dirty();
        }
      }
      const valueType = this._def.valueType;
      function finalizeSet(elements2) {
        const parsedSet = /* @__PURE__ */ new Set();
        for (const element of elements2) {
          if (element.status === "aborted")
            return INVALID;
          if (element.status === "dirty")
            status.dirty();
          parsedSet.add(element.value);
        }
        return { status: status.value, value: parsedSet };
      }
      const elements = [...ctx.data.values()].map((item, i) => valueType._parse(new ParseInputLazyPath(ctx, item, ctx.path, i)));
      if (ctx.common.async) {
        return Promise.all(elements).then((elements2) => finalizeSet(elements2));
      } else {
        return finalizeSet(elements);
      }
    }
    min(minSize, message) {
      return new _ZodSet({
        ...this._def,
        minSize: { value: minSize, message: errorUtil.toString(message) }
      });
    }
    max(maxSize, message) {
      return new _ZodSet({
        ...this._def,
        maxSize: { value: maxSize, message: errorUtil.toString(message) }
      });
    }
    size(size, message) {
      return this.min(size, message).max(size, message);
    }
    nonempty(message) {
      return this.min(1, message);
    }
  };
  ZodSet.create = (valueType, params) => {
    return new ZodSet({
      valueType,
      minSize: null,
      maxSize: null,
      typeName: ZodFirstPartyTypeKind.ZodSet,
      ...processCreateParams(params)
    });
  };
  var ZodFunction = class _ZodFunction extends ZodType {
    constructor() {
      super(...arguments);
      this.validate = this.implement;
    }
    _parse(input) {
      const { ctx } = this._processInputParams(input);
      if (ctx.parsedType !== ZodParsedType.function) {
        addIssueToContext(ctx, {
          code: ZodIssueCode.invalid_type,
          expected: ZodParsedType.function,
          received: ctx.parsedType
        });
        return INVALID;
      }
      function makeArgsIssue(args, error) {
        return makeIssue({
          data: args,
          path: ctx.path,
          errorMaps: [ctx.common.contextualErrorMap, ctx.schemaErrorMap, getErrorMap(), en_default].filter((x) => !!x),
          issueData: {
            code: ZodIssueCode.invalid_arguments,
            argumentsError: error
          }
        });
      }
      function makeReturnsIssue(returns, error) {
        return makeIssue({
          data: returns,
          path: ctx.path,
          errorMaps: [ctx.common.contextualErrorMap, ctx.schemaErrorMap, getErrorMap(), en_default].filter((x) => !!x),
          issueData: {
            code: ZodIssueCode.invalid_return_type,
            returnTypeError: error
          }
        });
      }
      const params = { errorMap: ctx.common.contextualErrorMap };
      const fn = ctx.data;
      if (this._def.returns instanceof ZodPromise) {
        const me = this;
        return OK(async function(...args) {
          const error = new ZodError([]);
          const parsedArgs = await me._def.args.parseAsync(args, params).catch((e) => {
            error.addIssue(makeArgsIssue(args, e));
            throw error;
          });
          const result = await Reflect.apply(fn, this, parsedArgs);
          const parsedReturns = await me._def.returns._def.type.parseAsync(result, params).catch((e) => {
            error.addIssue(makeReturnsIssue(result, e));
            throw error;
          });
          return parsedReturns;
        });
      } else {
        const me = this;
        return OK(function(...args) {
          const parsedArgs = me._def.args.safeParse(args, params);
          if (!parsedArgs.success) {
            throw new ZodError([makeArgsIssue(args, parsedArgs.error)]);
          }
          const result = Reflect.apply(fn, this, parsedArgs.data);
          const parsedReturns = me._def.returns.safeParse(result, params);
          if (!parsedReturns.success) {
            throw new ZodError([makeReturnsIssue(result, parsedReturns.error)]);
          }
          return parsedReturns.data;
        });
      }
    }
    parameters() {
      return this._def.args;
    }
    returnType() {
      return this._def.returns;
    }
    args(...items) {
      return new _ZodFunction({
        ...this._def,
        args: ZodTuple.create(items).rest(ZodUnknown.create())
      });
    }
    returns(returnType) {
      return new _ZodFunction({
        ...this._def,
        returns: returnType
      });
    }
    implement(func) {
      const validatedFunc = this.parse(func);
      return validatedFunc;
    }
    strictImplement(func) {
      const validatedFunc = this.parse(func);
      return validatedFunc;
    }
    static create(args, returns, params) {
      return new _ZodFunction({
        args: args ? args : ZodTuple.create([]).rest(ZodUnknown.create()),
        returns: returns || ZodUnknown.create(),
        typeName: ZodFirstPartyTypeKind.ZodFunction,
        ...processCreateParams(params)
      });
    }
  };
  var ZodLazy = class extends ZodType {
    get schema() {
      return this._def.getter();
    }
    _parse(input) {
      const { ctx } = this._processInputParams(input);
      const lazySchema = this._def.getter();
      return lazySchema._parse({ data: ctx.data, path: ctx.path, parent: ctx });
    }
  };
  ZodLazy.create = (getter, params) => {
    return new ZodLazy({
      getter,
      typeName: ZodFirstPartyTypeKind.ZodLazy,
      ...processCreateParams(params)
    });
  };
  var ZodLiteral = class extends ZodType {
    _parse(input) {
      if (input.data !== this._def.value) {
        const ctx = this._getOrReturnCtx(input);
        addIssueToContext(ctx, {
          received: ctx.data,
          code: ZodIssueCode.invalid_literal,
          expected: this._def.value
        });
        return INVALID;
      }
      return { status: "valid", value: input.data };
    }
    get value() {
      return this._def.value;
    }
  };
  ZodLiteral.create = (value, params) => {
    return new ZodLiteral({
      value,
      typeName: ZodFirstPartyTypeKind.ZodLiteral,
      ...processCreateParams(params)
    });
  };
  function createZodEnum(values, params) {
    return new ZodEnum({
      values,
      typeName: ZodFirstPartyTypeKind.ZodEnum,
      ...processCreateParams(params)
    });
  }
  var ZodEnum = class _ZodEnum extends ZodType {
    _parse(input) {
      if (typeof input.data !== "string") {
        const ctx = this._getOrReturnCtx(input);
        const expectedValues = this._def.values;
        addIssueToContext(ctx, {
          expected: util.joinValues(expectedValues),
          received: ctx.parsedType,
          code: ZodIssueCode.invalid_type
        });
        return INVALID;
      }
      if (!this._cache) {
        this._cache = new Set(this._def.values);
      }
      if (!this._cache.has(input.data)) {
        const ctx = this._getOrReturnCtx(input);
        const expectedValues = this._def.values;
        addIssueToContext(ctx, {
          received: ctx.data,
          code: ZodIssueCode.invalid_enum_value,
          options: expectedValues
        });
        return INVALID;
      }
      return OK(input.data);
    }
    get options() {
      return this._def.values;
    }
    get enum() {
      const enumValues = {};
      for (const val of this._def.values) {
        enumValues[val] = val;
      }
      return enumValues;
    }
    get Values() {
      const enumValues = {};
      for (const val of this._def.values) {
        enumValues[val] = val;
      }
      return enumValues;
    }
    get Enum() {
      const enumValues = {};
      for (const val of this._def.values) {
        enumValues[val] = val;
      }
      return enumValues;
    }
    extract(values, newDef = this._def) {
      return _ZodEnum.create(values, {
        ...this._def,
        ...newDef
      });
    }
    exclude(values, newDef = this._def) {
      return _ZodEnum.create(this.options.filter((opt) => !values.includes(opt)), {
        ...this._def,
        ...newDef
      });
    }
  };
  ZodEnum.create = createZodEnum;
  var ZodNativeEnum = class extends ZodType {
    _parse(input) {
      const nativeEnumValues = util.getValidEnumValues(this._def.values);
      const ctx = this._getOrReturnCtx(input);
      if (ctx.parsedType !== ZodParsedType.string && ctx.parsedType !== ZodParsedType.number) {
        const expectedValues = util.objectValues(nativeEnumValues);
        addIssueToContext(ctx, {
          expected: util.joinValues(expectedValues),
          received: ctx.parsedType,
          code: ZodIssueCode.invalid_type
        });
        return INVALID;
      }
      if (!this._cache) {
        this._cache = new Set(util.getValidEnumValues(this._def.values));
      }
      if (!this._cache.has(input.data)) {
        const expectedValues = util.objectValues(nativeEnumValues);
        addIssueToContext(ctx, {
          received: ctx.data,
          code: ZodIssueCode.invalid_enum_value,
          options: expectedValues
        });
        return INVALID;
      }
      return OK(input.data);
    }
    get enum() {
      return this._def.values;
    }
  };
  ZodNativeEnum.create = (values, params) => {
    return new ZodNativeEnum({
      values,
      typeName: ZodFirstPartyTypeKind.ZodNativeEnum,
      ...processCreateParams(params)
    });
  };
  var ZodPromise = class extends ZodType {
    unwrap() {
      return this._def.type;
    }
    _parse(input) {
      const { ctx } = this._processInputParams(input);
      if (ctx.parsedType !== ZodParsedType.promise && ctx.common.async === false) {
        addIssueToContext(ctx, {
          code: ZodIssueCode.invalid_type,
          expected: ZodParsedType.promise,
          received: ctx.parsedType
        });
        return INVALID;
      }
      const promisified = ctx.parsedType === ZodParsedType.promise ? ctx.data : Promise.resolve(ctx.data);
      return OK(promisified.then((data) => {
        return this._def.type.parseAsync(data, {
          path: ctx.path,
          errorMap: ctx.common.contextualErrorMap
        });
      }));
    }
  };
  ZodPromise.create = (schema, params) => {
    return new ZodPromise({
      type: schema,
      typeName: ZodFirstPartyTypeKind.ZodPromise,
      ...processCreateParams(params)
    });
  };
  var ZodEffects = class extends ZodType {
    innerType() {
      return this._def.schema;
    }
    sourceType() {
      return this._def.schema._def.typeName === ZodFirstPartyTypeKind.ZodEffects ? this._def.schema.sourceType() : this._def.schema;
    }
    _parse(input) {
      const { status, ctx } = this._processInputParams(input);
      const effect = this._def.effect || null;
      const checkCtx = {
        addIssue: (arg) => {
          addIssueToContext(ctx, arg);
          if (arg.fatal) {
            status.abort();
          } else {
            status.dirty();
          }
        },
        get path() {
          return ctx.path;
        }
      };
      checkCtx.addIssue = checkCtx.addIssue.bind(checkCtx);
      if (effect.type === "preprocess") {
        const processed = effect.transform(ctx.data, checkCtx);
        if (ctx.common.async) {
          return Promise.resolve(processed).then(async (processed2) => {
            if (status.value === "aborted")
              return INVALID;
            const result = await this._def.schema._parseAsync({
              data: processed2,
              path: ctx.path,
              parent: ctx
            });
            if (result.status === "aborted")
              return INVALID;
            if (result.status === "dirty")
              return DIRTY(result.value);
            if (status.value === "dirty")
              return DIRTY(result.value);
            return result;
          });
        } else {
          if (status.value === "aborted")
            return INVALID;
          const result = this._def.schema._parseSync({
            data: processed,
            path: ctx.path,
            parent: ctx
          });
          if (result.status === "aborted")
            return INVALID;
          if (result.status === "dirty")
            return DIRTY(result.value);
          if (status.value === "dirty")
            return DIRTY(result.value);
          return result;
        }
      }
      if (effect.type === "refinement") {
        const executeRefinement = (acc) => {
          const result = effect.refinement(acc, checkCtx);
          if (ctx.common.async) {
            return Promise.resolve(result);
          }
          if (result instanceof Promise) {
            throw new Error("Async refinement encountered during synchronous parse operation. Use .parseAsync instead.");
          }
          return acc;
        };
        if (ctx.common.async === false) {
          const inner = this._def.schema._parseSync({
            data: ctx.data,
            path: ctx.path,
            parent: ctx
          });
          if (inner.status === "aborted")
            return INVALID;
          if (inner.status === "dirty")
            status.dirty();
          executeRefinement(inner.value);
          return { status: status.value, value: inner.value };
        } else {
          return this._def.schema._parseAsync({ data: ctx.data, path: ctx.path, parent: ctx }).then((inner) => {
            if (inner.status === "aborted")
              return INVALID;
            if (inner.status === "dirty")
              status.dirty();
            return executeRefinement(inner.value).then(() => {
              return { status: status.value, value: inner.value };
            });
          });
        }
      }
      if (effect.type === "transform") {
        if (ctx.common.async === false) {
          const base = this._def.schema._parseSync({
            data: ctx.data,
            path: ctx.path,
            parent: ctx
          });
          if (!isValid(base))
            return INVALID;
          const result = effect.transform(base.value, checkCtx);
          if (result instanceof Promise) {
            throw new Error(`Asynchronous transform encountered during synchronous parse operation. Use .parseAsync instead.`);
          }
          return { status: status.value, value: result };
        } else {
          return this._def.schema._parseAsync({ data: ctx.data, path: ctx.path, parent: ctx }).then((base) => {
            if (!isValid(base))
              return INVALID;
            return Promise.resolve(effect.transform(base.value, checkCtx)).then((result) => ({
              status: status.value,
              value: result
            }));
          });
        }
      }
      util.assertNever(effect);
    }
  };
  ZodEffects.create = (schema, effect, params) => {
    return new ZodEffects({
      schema,
      typeName: ZodFirstPartyTypeKind.ZodEffects,
      effect,
      ...processCreateParams(params)
    });
  };
  ZodEffects.createWithPreprocess = (preprocess, schema, params) => {
    return new ZodEffects({
      schema,
      effect: { type: "preprocess", transform: preprocess },
      typeName: ZodFirstPartyTypeKind.ZodEffects,
      ...processCreateParams(params)
    });
  };
  var ZodOptional = class extends ZodType {
    _parse(input) {
      const parsedType = this._getType(input);
      if (parsedType === ZodParsedType.undefined) {
        return OK(void 0);
      }
      return this._def.innerType._parse(input);
    }
    unwrap() {
      return this._def.innerType;
    }
  };
  ZodOptional.create = (type, params) => {
    return new ZodOptional({
      innerType: type,
      typeName: ZodFirstPartyTypeKind.ZodOptional,
      ...processCreateParams(params)
    });
  };
  var ZodNullable = class extends ZodType {
    _parse(input) {
      const parsedType = this._getType(input);
      if (parsedType === ZodParsedType.null) {
        return OK(null);
      }
      return this._def.innerType._parse(input);
    }
    unwrap() {
      return this._def.innerType;
    }
  };
  ZodNullable.create = (type, params) => {
    return new ZodNullable({
      innerType: type,
      typeName: ZodFirstPartyTypeKind.ZodNullable,
      ...processCreateParams(params)
    });
  };
  var ZodDefault = class extends ZodType {
    _parse(input) {
      const { ctx } = this._processInputParams(input);
      let data = ctx.data;
      if (ctx.parsedType === ZodParsedType.undefined) {
        data = this._def.defaultValue();
      }
      return this._def.innerType._parse({
        data,
        path: ctx.path,
        parent: ctx
      });
    }
    removeDefault() {
      return this._def.innerType;
    }
  };
  ZodDefault.create = (type, params) => {
    return new ZodDefault({
      innerType: type,
      typeName: ZodFirstPartyTypeKind.ZodDefault,
      defaultValue: typeof params.default === "function" ? params.default : () => params.default,
      ...processCreateParams(params)
    });
  };
  var ZodCatch = class extends ZodType {
    _parse(input) {
      const { ctx } = this._processInputParams(input);
      const newCtx = {
        ...ctx,
        common: {
          ...ctx.common,
          issues: []
        }
      };
      const result = this._def.innerType._parse({
        data: newCtx.data,
        path: newCtx.path,
        parent: {
          ...newCtx
        }
      });
      if (isAsync(result)) {
        return result.then((result2) => {
          return {
            status: "valid",
            value: result2.status === "valid" ? result2.value : this._def.catchValue({
              get error() {
                return new ZodError(newCtx.common.issues);
              },
              input: newCtx.data
            })
          };
        });
      } else {
        return {
          status: "valid",
          value: result.status === "valid" ? result.value : this._def.catchValue({
            get error() {
              return new ZodError(newCtx.common.issues);
            },
            input: newCtx.data
          })
        };
      }
    }
    removeCatch() {
      return this._def.innerType;
    }
  };
  ZodCatch.create = (type, params) => {
    return new ZodCatch({
      innerType: type,
      typeName: ZodFirstPartyTypeKind.ZodCatch,
      catchValue: typeof params.catch === "function" ? params.catch : () => params.catch,
      ...processCreateParams(params)
    });
  };
  var ZodNaN = class extends ZodType {
    _parse(input) {
      const parsedType = this._getType(input);
      if (parsedType !== ZodParsedType.nan) {
        const ctx = this._getOrReturnCtx(input);
        addIssueToContext(ctx, {
          code: ZodIssueCode.invalid_type,
          expected: ZodParsedType.nan,
          received: ctx.parsedType
        });
        return INVALID;
      }
      return { status: "valid", value: input.data };
    }
  };
  ZodNaN.create = (params) => {
    return new ZodNaN({
      typeName: ZodFirstPartyTypeKind.ZodNaN,
      ...processCreateParams(params)
    });
  };
  var BRAND = Symbol("zod_brand");
  var ZodBranded = class extends ZodType {
    _parse(input) {
      const { ctx } = this._processInputParams(input);
      const data = ctx.data;
      return this._def.type._parse({
        data,
        path: ctx.path,
        parent: ctx
      });
    }
    unwrap() {
      return this._def.type;
    }
  };
  var ZodPipeline = class _ZodPipeline extends ZodType {
    _parse(input) {
      const { status, ctx } = this._processInputParams(input);
      if (ctx.common.async) {
        const handleAsync = async () => {
          const inResult = await this._def.in._parseAsync({
            data: ctx.data,
            path: ctx.path,
            parent: ctx
          });
          if (inResult.status === "aborted")
            return INVALID;
          if (inResult.status === "dirty") {
            status.dirty();
            return DIRTY(inResult.value);
          } else {
            return this._def.out._parseAsync({
              data: inResult.value,
              path: ctx.path,
              parent: ctx
            });
          }
        };
        return handleAsync();
      } else {
        const inResult = this._def.in._parseSync({
          data: ctx.data,
          path: ctx.path,
          parent: ctx
        });
        if (inResult.status === "aborted")
          return INVALID;
        if (inResult.status === "dirty") {
          status.dirty();
          return {
            status: "dirty",
            value: inResult.value
          };
        } else {
          return this._def.out._parseSync({
            data: inResult.value,
            path: ctx.path,
            parent: ctx
          });
        }
      }
    }
    static create(a, b) {
      return new _ZodPipeline({
        in: a,
        out: b,
        typeName: ZodFirstPartyTypeKind.ZodPipeline
      });
    }
  };
  var ZodReadonly = class extends ZodType {
    _parse(input) {
      const result = this._def.innerType._parse(input);
      const freeze = (data) => {
        if (isValid(data)) {
          data.value = Object.freeze(data.value);
        }
        return data;
      };
      return isAsync(result) ? result.then((data) => freeze(data)) : freeze(result);
    }
    unwrap() {
      return this._def.innerType;
    }
  };
  ZodReadonly.create = (type, params) => {
    return new ZodReadonly({
      innerType: type,
      typeName: ZodFirstPartyTypeKind.ZodReadonly,
      ...processCreateParams(params)
    });
  };
  function cleanParams(params, data) {
    const p = typeof params === "function" ? params(data) : typeof params === "string" ? { message: params } : params;
    const p2 = typeof p === "string" ? { message: p } : p;
    return p2;
  }
  function custom(check, _params = {}, fatal) {
    if (check)
      return ZodAny.create().superRefine((data, ctx) => {
        var _a, _b;
        const r = check(data);
        if (r instanceof Promise) {
          return r.then((r2) => {
            var _a2, _b2;
            if (!r2) {
              const params = cleanParams(_params, data);
              const _fatal = (_b2 = (_a2 = params.fatal) != null ? _a2 : fatal) != null ? _b2 : true;
              ctx.addIssue({ code: "custom", ...params, fatal: _fatal });
            }
          });
        }
        if (!r) {
          const params = cleanParams(_params, data);
          const _fatal = (_b = (_a = params.fatal) != null ? _a : fatal) != null ? _b : true;
          ctx.addIssue({ code: "custom", ...params, fatal: _fatal });
        }
        return;
      });
    return ZodAny.create();
  }
  var late = {
    object: ZodObject.lazycreate
  };
  var ZodFirstPartyTypeKind;
  (function(ZodFirstPartyTypeKind2) {
    ZodFirstPartyTypeKind2["ZodString"] = "ZodString";
    ZodFirstPartyTypeKind2["ZodNumber"] = "ZodNumber";
    ZodFirstPartyTypeKind2["ZodNaN"] = "ZodNaN";
    ZodFirstPartyTypeKind2["ZodBigInt"] = "ZodBigInt";
    ZodFirstPartyTypeKind2["ZodBoolean"] = "ZodBoolean";
    ZodFirstPartyTypeKind2["ZodDate"] = "ZodDate";
    ZodFirstPartyTypeKind2["ZodSymbol"] = "ZodSymbol";
    ZodFirstPartyTypeKind2["ZodUndefined"] = "ZodUndefined";
    ZodFirstPartyTypeKind2["ZodNull"] = "ZodNull";
    ZodFirstPartyTypeKind2["ZodAny"] = "ZodAny";
    ZodFirstPartyTypeKind2["ZodUnknown"] = "ZodUnknown";
    ZodFirstPartyTypeKind2["ZodNever"] = "ZodNever";
    ZodFirstPartyTypeKind2["ZodVoid"] = "ZodVoid";
    ZodFirstPartyTypeKind2["ZodArray"] = "ZodArray";
    ZodFirstPartyTypeKind2["ZodObject"] = "ZodObject";
    ZodFirstPartyTypeKind2["ZodUnion"] = "ZodUnion";
    ZodFirstPartyTypeKind2["ZodDiscriminatedUnion"] = "ZodDiscriminatedUnion";
    ZodFirstPartyTypeKind2["ZodIntersection"] = "ZodIntersection";
    ZodFirstPartyTypeKind2["ZodTuple"] = "ZodTuple";
    ZodFirstPartyTypeKind2["ZodRecord"] = "ZodRecord";
    ZodFirstPartyTypeKind2["ZodMap"] = "ZodMap";
    ZodFirstPartyTypeKind2["ZodSet"] = "ZodSet";
    ZodFirstPartyTypeKind2["ZodFunction"] = "ZodFunction";
    ZodFirstPartyTypeKind2["ZodLazy"] = "ZodLazy";
    ZodFirstPartyTypeKind2["ZodLiteral"] = "ZodLiteral";
    ZodFirstPartyTypeKind2["ZodEnum"] = "ZodEnum";
    ZodFirstPartyTypeKind2["ZodEffects"] = "ZodEffects";
    ZodFirstPartyTypeKind2["ZodNativeEnum"] = "ZodNativeEnum";
    ZodFirstPartyTypeKind2["ZodOptional"] = "ZodOptional";
    ZodFirstPartyTypeKind2["ZodNullable"] = "ZodNullable";
    ZodFirstPartyTypeKind2["ZodDefault"] = "ZodDefault";
    ZodFirstPartyTypeKind2["ZodCatch"] = "ZodCatch";
    ZodFirstPartyTypeKind2["ZodPromise"] = "ZodPromise";
    ZodFirstPartyTypeKind2["ZodBranded"] = "ZodBranded";
    ZodFirstPartyTypeKind2["ZodPipeline"] = "ZodPipeline";
    ZodFirstPartyTypeKind2["ZodReadonly"] = "ZodReadonly";
  })(ZodFirstPartyTypeKind || (ZodFirstPartyTypeKind = {}));
  var instanceOfType = (cls, params = {
    message: `Input not instance of ${cls.name}`
  }) => custom((data) => data instanceof cls, params);
  var stringType = ZodString.create;
  var numberType = ZodNumber.create;
  var nanType = ZodNaN.create;
  var bigIntType = ZodBigInt.create;
  var booleanType = ZodBoolean.create;
  var dateType = ZodDate.create;
  var symbolType = ZodSymbol.create;
  var undefinedType = ZodUndefined.create;
  var nullType = ZodNull.create;
  var anyType = ZodAny.create;
  var unknownType = ZodUnknown.create;
  var neverType = ZodNever.create;
  var voidType = ZodVoid.create;
  var arrayType = ZodArray.create;
  var objectType = ZodObject.create;
  var strictObjectType = ZodObject.strictCreate;
  var unionType = ZodUnion.create;
  var discriminatedUnionType = ZodDiscriminatedUnion.create;
  var intersectionType = ZodIntersection.create;
  var tupleType = ZodTuple.create;
  var recordType = ZodRecord.create;
  var mapType = ZodMap.create;
  var setType = ZodSet.create;
  var functionType = ZodFunction.create;
  var lazyType = ZodLazy.create;
  var literalType = ZodLiteral.create;
  var enumType = ZodEnum.create;
  var nativeEnumType = ZodNativeEnum.create;
  var promiseType = ZodPromise.create;
  var effectsType = ZodEffects.create;
  var optionalType = ZodOptional.create;
  var nullableType = ZodNullable.create;
  var preprocessType = ZodEffects.createWithPreprocess;
  var pipelineType = ZodPipeline.create;
  var ostring = () => stringType().optional();
  var onumber = () => numberType().optional();
  var oboolean = () => booleanType().optional();
  var coerce = {
    string: ((arg) => ZodString.create({ ...arg, coerce: true })),
    number: ((arg) => ZodNumber.create({ ...arg, coerce: true })),
    boolean: ((arg) => ZodBoolean.create({
      ...arg,
      coerce: true
    })),
    bigint: ((arg) => ZodBigInt.create({ ...arg, coerce: true })),
    date: ((arg) => ZodDate.create({ ...arg, coerce: true }))
  };
  var NEVER = INVALID;

  // node_modules/.pnpm/@debugbundle+shared-types@0.1.8/node_modules/@debugbundle/shared-types/dist/capture-policy.js
  var EventClassValues = [
    "incident_signal",
    "context_signal",
    "operational_signal"
  ];
  var EventClassSchema = external_exports.enum(EventClassValues);
  var CapturePresetValues = [
    "minimal",
    "balanced",
    "investigative"
  ];
  var CapturePresetSchema = external_exports.enum(CapturePresetValues);
  var CaptureLogsValues = ["off", "error", "warning", "info"];
  var CaptureLogsSchema = external_exports.enum(CaptureLogsValues);
  var CaptureRequestEventsValues = ["off", "failures_only", "filtered", "all"];
  var CaptureRequestEventsSchema = external_exports.enum(CaptureRequestEventsValues);
  var CaptureBreadcrumbsValues = ["local_only", "exception_only", "standalone"];
  var CaptureBreadcrumbsSchema = external_exports.enum(CaptureBreadcrumbsValues);
  var CaptureProbeEventsValues = ["buffer_only", "standalone_when_activated"];
  var CaptureProbeEventsSchema = external_exports.enum(CaptureProbeEventsValues);
  var RequestSignalClassificationValues = ["incident_signal", "context_signal"];
  var RequestSignalClassificationSchema = external_exports.enum(RequestSignalClassificationValues);
  var RECOMMENDED_IMMEDIATE_CLIENT_ERROR_STATUSES = [401, 403, 409, 422];
  var ImmediateClientErrorStatusSchema = external_exports.number().int().min(400).max(499);
  function normalizeImmediateClientErrorStatuses(statuses) {
    return Array.from(new Set(statuses)).sort((left, right) => left - right);
  }
  var ImmediateClientErrorStatusesSchema = external_exports.array(ImmediateClientErrorStatusSchema).max(12).transform((statuses) => normalizeImmediateClientErrorStatuses(statuses));
  var ResolvedCapturePolicySchema = external_exports.object({
    preset: CapturePresetSchema,
    capture_logs: CaptureLogsSchema,
    capture_request_events: CaptureRequestEventsSchema,
    capture_breadcrumbs: CaptureBreadcrumbsSchema,
    capture_probe_events: CaptureProbeEventsSchema,
    immediate_client_error_statuses: ImmediateClientErrorStatusesSchema
  });
  var CapturePolicyOverridesSchema = external_exports.object({
    capture_logs: CaptureLogsSchema.nullable(),
    capture_request_events: CaptureRequestEventsSchema.nullable(),
    capture_breadcrumbs: CaptureBreadcrumbsSchema.nullable(),
    capture_probe_events: CaptureProbeEventsSchema.nullable(),
    immediate_client_error_statuses: ImmediateClientErrorStatusesSchema.nullable()
  });
  var CapturePolicyResponseSchema = external_exports.object({
    access_mode: external_exports.enum(["manage", "preview"]),
    policy: ResolvedCapturePolicySchema,
    overrides: CapturePolicyOverridesSchema
  });
  var CapturePolicySchema = external_exports.object({
    project_id: external_exports.string().uuid(),
    preset: CapturePresetSchema,
    capture_logs: CaptureLogsSchema.nullable(),
    capture_request_events: CaptureRequestEventsSchema.nullable(),
    capture_breadcrumbs: CaptureBreadcrumbsSchema.nullable(),
    capture_probe_events: CaptureProbeEventsSchema.nullable(),
    immediate_client_error_statuses: ImmediateClientErrorStatusesSchema.nullable(),
    updated_at: external_exports.string().datetime()
  });
  var CapturePolicyUpdateSchema = external_exports.object({
    preset: CapturePresetSchema.optional(),
    capture_logs: CaptureLogsSchema.nullable().optional(),
    capture_request_events: CaptureRequestEventsSchema.nullable().optional(),
    capture_breadcrumbs: CaptureBreadcrumbsSchema.nullable().optional(),
    capture_probe_events: CaptureProbeEventsSchema.nullable().optional(),
    immediate_client_error_statuses: ImmediateClientErrorStatusesSchema.nullable().optional()
  });
  var PRESET_DEFAULTS = {
    minimal: {
      capture_logs: "error",
      capture_request_events: "failures_only",
      capture_breadcrumbs: "local_only",
      capture_probe_events: "buffer_only",
      immediate_client_error_statuses: []
    },
    balanced: {
      capture_logs: "warning",
      capture_request_events: "failures_only",
      capture_breadcrumbs: "exception_only",
      capture_probe_events: "buffer_only",
      immediate_client_error_statuses: []
    },
    investigative: {
      capture_logs: "info",
      capture_request_events: "all",
      capture_breadcrumbs: "standalone",
      capture_probe_events: "standalone_when_activated",
      immediate_client_error_statuses: [...RECOMMENDED_IMMEDIATE_CLIENT_ERROR_STATUSES]
    }
  };
  var BALANCED_IMMEDIATE_REQUEST_STATUSES = /* @__PURE__ */ new Set([408, 423, 424, 425, 429]);
  var INVESTIGATIVE_IMMEDIATE_REQUEST_STATUSES = /* @__PURE__ */ new Set([...BALANCED_IMMEDIATE_REQUEST_STATUSES, 409]);
  var BALANCED_STANDARD_ANOMALY_STATUSES = /* @__PURE__ */ new Set([401, 403, 404, 409, 422]);
  var BALANCED_HIGH_VOLUME_ANOMALY_STATUSES = /* @__PURE__ */ new Set([400, 410]);
  var INVESTIGATIVE_ANOMALY_STATUSES = /* @__PURE__ */ new Set([...BALANCED_STANDARD_ANOMALY_STATUSES, ...BALANCED_HIGH_VOLUME_ANOMALY_STATUSES]);

  // node_modules/.pnpm/@debugbundle+shared-types@0.1.8/node_modules/@debugbundle/shared-types/dist/improvement-settings.js
  var ImprovementBundleSensitivityValues = [
    "high_confidence",
    "balanced",
    "verbose"
  ];
  var ImprovementBundleSensitivitySchema = external_exports.enum(ImprovementBundleSensitivityValues);
  var ImprovementSettingsSchema = external_exports.object({
    automated_improvement_bundles_enabled: external_exports.boolean(),
    improvement_bundle_sensitivity: ImprovementBundleSensitivitySchema
  });
  var ImprovementSettingsResponseSchema = external_exports.object({
    access_mode: external_exports.enum(["manage", "preview"]),
    cloud_automation_available: external_exports.boolean(),
    settings: ImprovementSettingsSchema
  });
  var ImprovementSettingsUpdateSchema = external_exports.object({
    automated_improvement_bundles_enabled: external_exports.boolean().optional(),
    improvement_bundle_sensitivity: ImprovementBundleSensitivitySchema.optional()
  }).refine((input) => Object.keys(input).length > 0, {
    message: "At least one improvement settings field must be provided."
  });

  // node_modules/.pnpm/@debugbundle+shared-types@0.1.8/node_modules/@debugbundle/shared-types/dist/index.js
  function createUuidV4() {
    var _a, _b;
    const cryptoSource = globalThis.crypto;
    if (typeof (cryptoSource == null ? void 0 : cryptoSource.randomUUID) === "function") {
      return cryptoSource.randomUUID();
    }
    const bytes = cryptoSource.getRandomValues(new Uint8Array(16));
    const versionByte = (_a = bytes[6]) != null ? _a : 0;
    const variantByte = (_b = bytes[8]) != null ? _b : 0;
    bytes[6] = versionByte & 15 | 64;
    bytes[8] = variantByte & 63 | 128;
    const hex = Array.from(bytes, (byte) => byte.toString(16).padStart(2, "0"));
    return `${hex.slice(0, 4).join("")}-${hex.slice(4, 6).join("")}-${hex.slice(6, 8).join("")}-${hex.slice(8, 10).join("")}-${hex.slice(10, 16).join("")}`;
  }
  var EventTypeValues = [
    "backend_exception",
    "request_event",
    "log_event",
    "frontend_breadcrumb",
    "frontend_exception",
    "deploy_metadata",
    "error_suppressed",
    "probe_event"
  ];
  var EventTypeSchema = external_exports.enum(EventTypeValues);
  var ServiceSchema = external_exports.object({
    name: external_exports.string().min(1),
    runtime: external_exports.string().min(1).nullable().optional(),
    framework: external_exports.string().min(1).nullable().optional(),
    environment: external_exports.string().min(1)
  });
  var CorrelationSchema = external_exports.object({
    request_id: external_exports.string().nullable(),
    trace_id: external_exports.string().nullable(),
    session_id: external_exports.string().nullable(),
    user_id_hash: external_exports.string().nullable()
  }).strict();
  var InlineProbeDataItemSchema = external_exports.object({
    label: external_exports.string().min(1),
    data: external_exports.record(external_exports.string(), external_exports.unknown()),
    timestamp: external_exports.string().datetime(),
    activation_id: external_exports.string().uuid().nullable()
  }).strict();
  var InlineProbeDataSchema = external_exports.object({
    version: external_exports.literal(1),
    items: external_exports.array(InlineProbeDataItemSchema)
  }).strict();
  var RuntimeMemoryStatsSchema = external_exports.object({
    rss: external_exports.number().nonnegative().nullable(),
    heap_total: external_exports.number().nonnegative().nullable(),
    heap_used: external_exports.number().nonnegative().nullable(),
    external: external_exports.number().nonnegative().nullable(),
    peak: external_exports.number().nonnegative().nullable()
  }).strict();
  var BackendRuntimePayloadSchema = external_exports.object({
    version: external_exports.string().min(1),
    platform: external_exports.string().min(1).nullable().optional(),
    arch: external_exports.string().min(1).nullable().optional(),
    pid: external_exports.number().int().nonnegative().nullable().optional(),
    cwd: external_exports.string().min(1).nullable().optional(),
    uptime_sec: external_exports.number().nonnegative().nullable().optional(),
    hostname: external_exports.string().min(1).nullable().optional(),
    thread_id: external_exports.union([external_exports.string(), external_exports.number()]).nullable().optional(),
    framework_version: external_exports.string().min(1).nullable().optional(),
    memory: RuntimeMemoryStatsSchema.nullable().optional(),
    framework_extras: external_exports.record(external_exports.string(), external_exports.unknown()).nullable().optional()
  }).strict();
  var BackendExceptionPayloadSchema = external_exports.object({
    name: external_exports.string().min(1),
    message: external_exports.string().min(1),
    stack: external_exports.string().min(1),
    handled: external_exports.boolean(),
    request: external_exports.object({
      method: external_exports.string().min(1),
      path: external_exports.string().min(1),
      query: external_exports.record(external_exports.string(), external_exports.unknown()),
      headers: external_exports.record(external_exports.string(), external_exports.unknown()),
      body: external_exports.unknown().nullable().optional()
    }),
    response: external_exports.object({
      status_code: external_exports.number().int().nonnegative(),
      headers: external_exports.record(external_exports.string(), external_exports.unknown()).optional(),
      body: external_exports.unknown().optional()
    }),
    runtime: BackendRuntimePayloadSchema,
    probe_data: InlineProbeDataSchema.optional()
  }).strict();
  var RequestEventPayloadSchema = external_exports.object({
    method: external_exports.string().min(1),
    path: external_exports.string().min(1),
    query: external_exports.record(external_exports.string(), external_exports.unknown()),
    headers: external_exports.record(external_exports.string(), external_exports.unknown()),
    body: external_exports.unknown().nullable().optional(),
    response_status: external_exports.number().int().nonnegative(),
    duration_ms: external_exports.number().nonnegative(),
    route_template: external_exports.string().min(1).nullable().optional(),
    response_headers: external_exports.record(external_exports.string(), external_exports.unknown()).optional(),
    response_body: external_exports.unknown().optional()
  }).strict();
  var LogEventPayloadSchema = external_exports.object({
    level: external_exports.string().min(1),
    message: external_exports.string().min(1),
    attributes: external_exports.record(external_exports.string(), external_exports.unknown())
  }).strict();
  var FrontendBreadcrumbPayloadSchema = external_exports.object({
    breadcrumb_type: external_exports.enum(["route_change", "click", "form_submit", "console_log", "network_request"]),
    route: external_exports.string().min(1).nullable().optional(),
    data: external_exports.record(external_exports.string(), external_exports.unknown())
  }).strict();
  var FrontendExceptionBreadcrumbSchema = FrontendBreadcrumbPayloadSchema.extend({
    ts: external_exports.string().datetime()
  });
  var DeviceInfoSchema = external_exports.object({
    user_agent: external_exports.string().nullable(),
    os: external_exports.object({
      name: external_exports.string().nullable(),
      version: external_exports.string().nullable()
    }),
    device_type: external_exports.enum(["desktop", "mobile", "tablet", "unknown"]),
    screen: external_exports.object({
      width: external_exports.number().int().nonnegative(),
      height: external_exports.number().int().nonnegative()
    }),
    viewport: external_exports.object({
      width: external_exports.number().int().nonnegative(),
      height: external_exports.number().int().nonnegative()
    }),
    device_pixel_ratio: external_exports.number().positive().nullable(),
    touch_capable: external_exports.boolean().nullable(),
    language: external_exports.string().nullable(),
    connection_type: external_exports.string().nullable(),
    color_scheme_preference: external_exports.enum(["light", "dark", "no-preference"]).nullable()
  }).strict();
  var FrontendExceptionPayloadSchema = external_exports.object({
    name: external_exports.string().min(1),
    message: external_exports.string().min(1),
    stack: external_exports.string().min(1),
    route: external_exports.string().min(1).nullable().optional(),
    browser: external_exports.object({
      name: external_exports.string().min(1),
      version: external_exports.string().min(1)
    }),
    breadcrumbs: external_exports.array(FrontendExceptionBreadcrumbSchema).optional(),
    device: DeviceInfoSchema.nullable().optional(),
    dom_context: external_exports.object({
      mode: external_exports.literal("lightweight"),
      html_excerpt: external_exports.string().min(1)
    }).nullable().optional(),
    probe_data: InlineProbeDataSchema.optional()
  }).strict();
  var DeployMetadataPayloadSchema = external_exports.object({
    commit_sha: external_exports.string().min(1),
    version: external_exports.string().min(1),
    branch: external_exports.string().min(1),
    environment: external_exports.string().min(1),
    deployed_at: external_exports.string().datetime()
  }).strict();
  var ErrorSuppressedPayloadSchema = external_exports.object({
    fingerprint: external_exports.string().min(1),
    suppressed_count: external_exports.number().int().nonnegative(),
    window_seconds: external_exports.number().int().positive(),
    first_seen: external_exports.string().datetime(),
    last_seen: external_exports.string().datetime()
  }).strict();
  var ProbeEventPayloadSchema = external_exports.object({
    label: external_exports.string().min(1),
    data: external_exports.record(external_exports.string(), external_exports.unknown()),
    activation_id: external_exports.string().uuid().nullable(),
    probe_label_pattern: external_exports.string().min(1)
  }).strict();
  var EnvelopeBaseSchema = external_exports.object({
    schema_version: external_exports.string().min(1),
    event_id: external_exports.string().uuid(),
    event_type: EventTypeSchema,
    project_token: external_exports.string().min(1).optional(),
    project_id: external_exports.string().uuid().nullable().optional(),
    sdk_name: external_exports.string().min(1),
    sdk_version: external_exports.string().min(1),
    service: ServiceSchema,
    occurred_at: external_exports.string().datetime(),
    correlation: CorrelationSchema.optional()
  }).strict();
  var EventEnvelopeSchema = external_exports.discriminatedUnion("event_type", [
    EnvelopeBaseSchema.extend({ event_type: external_exports.literal("backend_exception"), payload: BackendExceptionPayloadSchema }),
    EnvelopeBaseSchema.extend({ event_type: external_exports.literal("request_event"), payload: RequestEventPayloadSchema }),
    EnvelopeBaseSchema.extend({ event_type: external_exports.literal("log_event"), payload: LogEventPayloadSchema }),
    EnvelopeBaseSchema.extend({ event_type: external_exports.literal("frontend_breadcrumb"), payload: FrontendBreadcrumbPayloadSchema }),
    EnvelopeBaseSchema.extend({ event_type: external_exports.literal("frontend_exception"), payload: FrontendExceptionPayloadSchema }),
    EnvelopeBaseSchema.extend({ event_type: external_exports.literal("deploy_metadata"), payload: DeployMetadataPayloadSchema }),
    EnvelopeBaseSchema.extend({ event_type: external_exports.literal("error_suppressed"), payload: ErrorSuppressedPayloadSchema }),
    EnvelopeBaseSchema.extend({ event_type: external_exports.literal("probe_event"), payload: ProbeEventPayloadSchema })
  ]);
  function createEventEnvelope(input) {
    var _a, _b, _c, _d, _e, _f;
    const candidate = {
      schema_version: (_a = input.schema_version) != null ? _a : "2026-03-01",
      event_id: (_b = input.event_id) != null ? _b : createUuidV4(),
      event_type: input.event_type,
      project_token: input.project_token,
      project_id: input.project_id,
      sdk_name: (_c = input.sdk_name) != null ? _c : "debugbundle-node",
      sdk_version: (_d = input.sdk_version) != null ? _d : "0.0.0",
      service: input.service,
      occurred_at: (_e = input.occurred_at) != null ? _e : (/* @__PURE__ */ new Date()).toISOString(),
      correlation: (_f = input.correlation) != null ? _f : {
        request_id: null,
        trace_id: null,
        session_id: null,
        user_id_hash: null
      },
      payload: input.payload
    };
    return EventEnvelopeSchema.parse(candidate);
  }
  var SeveritySchema = external_exports.enum(["low", "medium", "high", "critical"]);
  var BundleSdkSchema = external_exports.object({
    name: external_exports.string().min(1),
    version: external_exports.string().min(1)
  });
  var BundleProjectSchema = external_exports.object({
    id: external_exports.string().min(1),
    slug: external_exports.string().min(1),
    environment: external_exports.string().min(1)
  });
  var BundleServiceSchema = external_exports.object({
    id: external_exports.string().min(1),
    name: external_exports.string().min(1),
    runtime: external_exports.string().min(1).nullable(),
    framework: external_exports.string().min(1).nullable(),
    version: external_exports.string().min(1).nullable(),
    region: external_exports.string().min(1).nullable()
  });
  var SignalTypeSchema = external_exports.enum([
    "exception",
    "fatal_error",
    "request_failure",
    "frontend_exception",
    "warning",
    "deprecation",
    "performance_issue",
    "retry_loop",
    "slow_query"
  ]);
  var BundleSignalSchema = external_exports.object({
    signal_id: external_exports.string().min(1),
    signal_type: SignalTypeSchema,
    severity: SeveritySchema,
    fingerprint: external_exports.string().min(1),
    first_seen_at: external_exports.string().datetime(),
    last_seen_at: external_exports.string().datetime(),
    occurrence_count: external_exports.number().int().nonnegative(),
    source_event_types: external_exports.array(external_exports.string().min(1))
  });
  var FirstApplicationFrameSchema = external_exports.object({
    file: external_exports.string().nullable(),
    line: external_exports.number().int().nonnegative().nullable(),
    function: external_exports.string().nullable()
  });
  var SummarySignalsSchema = external_exports.object({
    new_deploy: external_exports.boolean(),
    regression_suspected: external_exports.boolean(),
    customer_visible: external_exports.boolean()
  });
  var BundleSummarySchema = external_exports.object({
    title: external_exports.string().min(1),
    description: external_exports.string().min(1),
    likely_cause: external_exports.string().nullable(),
    confidence: external_exports.number().min(0).max(1),
    recommended_action: external_exports.string().nullable(),
    severity: SeveritySchema,
    error_type: external_exports.string().nullable(),
    error_message: external_exports.string().nullable(),
    first_application_frame: FirstApplicationFrameSchema.nullable(),
    primary_signal: external_exports.string().nullable(),
    signals: SummarySignalsSchema
  });
  var BundleImpactSchema = external_exports.object({
    affected_users_estimate: external_exports.number().int().nonnegative().nullable(),
    affected_requests_estimate: external_exports.number().int().nonnegative().nullable(),
    business_criticality: SeveritySchema,
    customer_visible: external_exports.boolean(),
    regression_suspected: external_exports.boolean()
  });
  var ContextErrorSchema = external_exports.object({
    version: external_exports.literal(1),
    name: external_exports.string().min(1),
    message: external_exports.string().min(1),
    stack: external_exports.string().min(1),
    handled: external_exports.boolean(),
    top_frames: external_exports.array(external_exports.string())
  }).nullable();
  var ContextRequestSchema = external_exports.object({
    version: external_exports.literal(1),
    method: external_exports.string().min(1),
    path: external_exports.string().min(1),
    route_template: external_exports.string().nullable(),
    query: external_exports.record(external_exports.string(), external_exports.unknown()),
    headers: external_exports.record(external_exports.string(), external_exports.unknown()),
    body: external_exports.unknown().nullable(),
    request_id: external_exports.string().nullable()
  });
  var ContextResponseSchema = external_exports.object({
    version: external_exports.literal(1),
    status_code: external_exports.number().int(),
    duration_ms: external_exports.number().nonnegative().nullable(),
    headers: external_exports.record(external_exports.string(), external_exports.unknown()).optional(),
    body: external_exports.unknown().optional()
  });
  var LogItemSchema = external_exports.object({
    level: external_exports.string().min(1),
    message: external_exports.string().min(1),
    timestamp: external_exports.string().datetime(),
    attributes: external_exports.record(external_exports.string(), external_exports.unknown())
  });
  var ContextLogsSchema = external_exports.object({
    version: external_exports.literal(1),
    items: external_exports.array(LogItemSchema)
  });
  var ContextFrontendSchema = external_exports.object({
    version: external_exports.literal(1),
    route_changes: external_exports.array(external_exports.object({ from: external_exports.string(), to: external_exports.string(), ts: external_exports.string().datetime() })),
    clicks: external_exports.array(external_exports.object({ selector: external_exports.string(), label: external_exports.string(), ts: external_exports.string().datetime() })),
    form_submissions: external_exports.array(external_exports.object({ form: external_exports.string(), fields: external_exports.record(external_exports.string(), external_exports.unknown()), ts: external_exports.string().datetime() })),
    console_logs: external_exports.array(external_exports.unknown()),
    network_requests: external_exports.array(external_exports.object({
      method: external_exports.string(),
      url: external_exports.string(),
      status: external_exports.number().int(),
      ts: external_exports.string().datetime(),
      duration_ms: external_exports.number().nonnegative().optional(),
      caller_trace: external_exports.array(external_exports.string()).optional(),
      response_body: external_exports.unknown().optional(),
      request_body: external_exports.unknown().optional(),
      response_headers: external_exports.record(external_exports.string(), external_exports.string()).optional(),
      response_content_length: external_exports.number().int().nonnegative().optional()
    })),
    exceptions: external_exports.array(external_exports.unknown()),
    dom_context: external_exports.object({
      mode: external_exports.literal("lightweight"),
      html_excerpt: external_exports.string()
    }).nullable()
  });
  var ContextEnvironmentSchema = external_exports.object({
    version: external_exports.literal(1),
    os: external_exports.string().nullable(),
    host: external_exports.string().nullable(),
    container_id: external_exports.string().nullable()
  });
  var ContextDeploySchema = external_exports.object({
    version: external_exports.literal(1),
    commit_sha: external_exports.string().nullable(),
    deploy_version: external_exports.string().nullable(),
    branch: external_exports.string().nullable(),
    deployed_at: external_exports.string().datetime().nullable(),
    regression_window: external_exports.boolean().nullable()
  });
  var MemoryStatsSchema = RuntimeMemoryStatsSchema;
  var ContextRuntimeSchema = external_exports.object({
    version: external_exports.literal(1),
    name: external_exports.string().min(1),
    runtime_version: external_exports.string().nullable(),
    platform: external_exports.string().nullable(),
    arch: external_exports.string().nullable(),
    pid: external_exports.number().int().nullable(),
    cwd: external_exports.string().nullable(),
    uptime_sec: external_exports.number().nonnegative().nullable(),
    hostname: external_exports.string().nullable(),
    thread_id: external_exports.union([external_exports.string(), external_exports.number()]).nullable(),
    framework: external_exports.string().nullable(),
    framework_version: external_exports.string().nullable(),
    memory: MemoryStatsSchema.nullable(),
    framework_extras: external_exports.record(external_exports.string(), external_exports.unknown()).nullable().optional()
  });
  var ContextGitSchema = external_exports.object({
    version: external_exports.literal(1),
    commit: external_exports.string().nullable(),
    commit_short: external_exports.string().nullable(),
    branch: external_exports.string().nullable(),
    repo: external_exports.string().nullable(),
    dirty: external_exports.boolean(),
    source: external_exports.enum(["config", "env", "local", "unknown"])
  });
  var DependencyItemSchema = external_exports.object({
    name: external_exports.string().min(1),
    status: external_exports.enum(["ok", "degraded", "failed", "unknown"]),
    notes: external_exports.string().nullable()
  });
  var ContextDependenciesSchema = external_exports.object({
    version: external_exports.literal(1),
    items: external_exports.array(DependencyItemSchema)
  });
  var ProbeDataItemSchema = external_exports.object({
    label: external_exports.string().min(1),
    data: external_exports.record(external_exports.string(), external_exports.unknown()),
    timestamp: external_exports.string().datetime(),
    activation_id: external_exports.string().uuid().nullable()
  });
  var ContextProbeDataSchema = external_exports.object({
    version: external_exports.literal(1),
    items: external_exports.array(ProbeDataItemSchema)
  });
  var ContextDeviceSchema = external_exports.object({
    version: external_exports.literal(1),
    user_agent: external_exports.string().nullable(),
    browser: external_exports.object({
      name: external_exports.string().nullable(),
      version: external_exports.string().nullable()
    }),
    os: external_exports.object({
      name: external_exports.string().nullable(),
      version: external_exports.string().nullable()
    }),
    device_type: external_exports.enum(["desktop", "mobile", "tablet", "unknown"]),
    screen: external_exports.object({
      width: external_exports.number().int().nonnegative(),
      height: external_exports.number().int().nonnegative()
    }),
    viewport: external_exports.object({
      width: external_exports.number().int().nonnegative(),
      height: external_exports.number().int().nonnegative()
    }),
    device_pixel_ratio: external_exports.number().positive().nullable(),
    touch_capable: external_exports.boolean().nullable(),
    language: external_exports.string().nullable(),
    connection_type: external_exports.string().nullable(),
    color_scheme_preference: external_exports.enum(["light", "dark", "no-preference"]).nullable()
  });
  var BundleContextSchema = external_exports.object({
    error: ContextErrorSchema.nullable().optional(),
    request: ContextRequestSchema.nullable().optional(),
    response: ContextResponseSchema.nullable().optional(),
    logs: ContextLogsSchema.nullable().optional(),
    frontend: ContextFrontendSchema.nullable().optional(),
    environment: ContextEnvironmentSchema.nullable().optional(),
    deploy: ContextDeploySchema.nullable().optional(),
    runtime: ContextRuntimeSchema.nullable().optional(),
    git: ContextGitSchema.nullable().optional(),
    dependencies: ContextDependenciesSchema.nullable().optional(),
    probe_data: ContextProbeDataSchema.nullable().optional(),
    device: ContextDeviceSchema.nullable().optional()
  });
  var ReproductionArtifactsSchema = external_exports.object({
    curl: external_exports.string().nullable(),
    httpie: external_exports.string().nullable(),
    json_spec: external_exports.object({
      method: external_exports.string(),
      url: external_exports.string(),
      headers: external_exports.record(external_exports.string(), external_exports.unknown()),
      query: external_exports.record(external_exports.string(), external_exports.unknown()).optional(),
      body: external_exports.unknown().nullable()
    }).nullable()
  });
  var FeasibilityReferenceSchema = external_exports.object({
    standard_http_bugs: external_exports.string(),
    frontend_interaction_plus_failing_request: external_exports.string(),
    background_jobs: external_exports.string(),
    race_conditions: external_exports.string(),
    external_outage_timing: external_exports.string()
  });
  var BundleReproductionSchema = external_exports.object({
    possible: external_exports.boolean(),
    confidence: external_exports.number().min(0).max(1),
    reason: external_exports.string(),
    artifacts: ReproductionArtifactsSchema.nullable(),
    feasibility_reference: FeasibilityReferenceSchema.nullable().optional()
  });
  var BundleVerificationSchema = external_exports.object({
    verification_type: external_exports.string().nullable(),
    synthetic: external_exports.boolean(),
    local_verified: external_exports.boolean(),
    production_verified: external_exports.boolean()
  });
  var BundleLinksSchema = external_exports.object({
    self: external_exports.string().nullable(),
    reproduction: external_exports.string().nullable(),
    incident: external_exports.string().nullable(),
    project: external_exports.string().nullable(),
    docs: external_exports.string().nullable()
  });
  var BundleRedactionSchema = external_exports.object({
    redacted: external_exports.boolean(),
    fields: external_exports.array(external_exports.string()),
    notes: external_exports.string().nullable()
  });
  var BundleMetadataSchema = external_exports.object({
    created_at: external_exports.string().datetime(),
    updated_at: external_exports.string().datetime(),
    generator_version: external_exports.string().min(1),
    generation_number: external_exports.number().int().positive()
  });
  var BundleV1Schema = external_exports.object({
    bundle_version: external_exports.literal(1),
    bundle_id: external_exports.string().min(1),
    bundle_type: external_exports.enum(["failure", "improvement"]),
    captured_at: external_exports.string().datetime(),
    sdk: BundleSdkSchema,
    project: BundleProjectSchema,
    service: BundleServiceSchema,
    signal: BundleSignalSchema,
    summary: BundleSummarySchema,
    impact: BundleImpactSchema,
    context: BundleContextSchema,
    reproduction: BundleReproductionSchema,
    verification: BundleVerificationSchema,
    links: BundleLinksSchema,
    redaction: BundleRedactionSchema,
    metadata: BundleMetadataSchema
  });

  // node_modules/.pnpm/@debugbundle+sdk-browser@0.1.8/node_modules/@debugbundle/sdk-browser/dist/types.js
  var SDK_NAME = "@debugbundle/sdk-browser";
  var SDK_VERSION = "0.1.0";
  var SDK_SCHEMA_VERSION = "2026-03-01";
  var DEFAULT_ENDPOINT = "https://api.debugbundle.com/v1/events";
  var DEFAULT_BATCH_SIZE = 10;
  var DEFAULT_FLUSH_INTERVAL_MS = 3e3;
  var DEFAULT_REQUEST_TIMEOUT_MS = 5e3;
  var DEFAULT_MAX_BREADCRUMBS = 10;
  var DEFAULT_SAMPLE_RATE = 1;
  var DEFAULT_SESSION_SAMPLE_RATE = 1;
  var DEFAULT_MAX_EVENTS_PER_SESSION = 100;
  var MAX_BODY_CAPTURE_BYTES = 4096;
  var LOG_LEVELS = ["debug", "info", "warning", "error", "critical"];
  var LOG_LEVEL_ORDER = {
    debug: 10,
    info: 20,
    warning: 30,
    error: 40,
    critical: 50
  };
  var DEFAULT_LOG_LEVEL = "warning";

  // node_modules/.pnpm/@debugbundle+sdk-browser@0.1.8/node_modules/@debugbundle/sdk-browser/dist/runtime.js
  var DEFAULT_REQUEST_FAILURE_PRESET = "balanced";
  var DEFAULT_REQUEST_CAPTURE_EVENTS = "failures_only";
  var DEFAULT_IMMEDIATE_CLIENT_ERROR_STATUSES = [];
  function getWindowSource() {
    const candidate = globalThis["window"];
    if (candidate === null || typeof candidate !== "object") {
      return null;
    }
    return candidate;
  }
  function getDocumentSource() {
    const candidate = globalThis["document"];
    if (candidate === null || typeof candidate !== "object") {
      return null;
    }
    return candidate;
  }
  function getHistorySource() {
    const candidate = globalThis["history"];
    if (candidate === null || typeof candidate !== "object") {
      return null;
    }
    return candidate;
  }
  function getNavigatorSource() {
    const candidate = globalThis["navigator"];
    if (candidate === null || typeof candidate !== "object") {
      return null;
    }
    return candidate;
  }
  function getLocationSource() {
    const candidate = globalThis["location"];
    if (candidate === null || typeof candidate !== "object") {
      return null;
    }
    return candidate;
  }
  function getScreenSource() {
    const candidate = globalThis["screen"];
    if (candidate === null || typeof candidate !== "object") {
      return null;
    }
    return candidate;
  }
  function getMatchMedia() {
    const candidate = globalThis["matchMedia"];
    return typeof candidate === "function" ? candidate : null;
  }
  function getFetchSource() {
    const candidate = globalThis["fetch"];
    return typeof candidate === "function" ? candidate : null;
  }
  function getConsoleSource() {
    const candidate = globalThis["console"];
    if (candidate === null || typeof candidate !== "object") {
      return null;
    }
    return candidate;
  }
  function getCryptoSource() {
    const candidate = globalThis["crypto"];
    if (candidate === null || typeof candidate !== "object") {
      return null;
    }
    return candidate;
  }
  function getXmlHttpRequestConstructor() {
    const candidate = globalThis["XMLHttpRequest"];
    return typeof candidate === "function" ? candidate : null;
  }
  function normalizePositiveNumber(value, fallback) {
    if (typeof value !== "number" || !Number.isFinite(value) || value < 1) {
      return fallback;
    }
    return Math.floor(value);
  }
  function normalizeSampleRate(value, fallback) {
    if (typeof value !== "number" || !Number.isFinite(value)) {
      return fallback;
    }
    if (value <= 0) {
      return 0;
    }
    if (value >= 1) {
      return 1;
    }
    return value;
  }
  function normalizeLogLevel(level) {
    return LOG_LEVELS.includes(level) ? level : DEFAULT_LOG_LEVEL;
  }
  function normalizeUnknownRecord(value) {
    if (value === null || typeof value !== "object" || Array.isArray(value)) {
      return {};
    }
    return Object.fromEntries(Object.entries(value));
  }
  function normalizeError(error) {
    if (error instanceof Error) {
      return {
        name: error.name || "Error",
        message: error.message || "Unknown browser error",
        stack: error.stack || `${error.name || "Error"}: ${error.message || "Unknown browser error"}`
      };
    }
    if (typeof error === "string") {
      return {
        name: "Error",
        message: error,
        stack: `Error: ${error}`
      };
    }
    return {
      name: "Error",
      message: "Unknown browser error",
      stack: "Error: Unknown browser error"
    };
  }
  function captureCallerTrace(skipFrames, maxFrames) {
    var _a;
    const sentinel = {};
    const captureStackTrace = Error.captureStackTrace;
    if (typeof captureStackTrace === "function") {
      captureStackTrace(sentinel, captureCallerTrace);
    } else {
      sentinel.stack = (_a = new Error().stack) != null ? _a : "";
      skipFrames += 1;
    }
    const stack = sentinel.stack;
    if (!stack)
      return [];
    const lines = stack.split("\n");
    const frames = [];
    let frameIndex = 0;
    for (const line of lines) {
      const trimmed = line.trim();
      if (!trimmed || /^Error:?\s*$/.test(trimmed))
        continue;
      if (frameIndex < skipFrames) {
        frameIndex++;
        continue;
      }
      frames.push(trimmed.startsWith("at ") ? trimmed.slice(3) : trimmed);
      if (frames.length >= maxFrames)
        break;
      frameIndex++;
    }
    return frames;
  }
  function parseRetryAfter(value) {
    if (value === null) {
      return void 0;
    }
    const seconds = Number(value);
    if (Number.isFinite(seconds)) {
      return Math.max(0, seconds * 1e3);
    }
    const parsed = Date.parse(value);
    if (Number.isNaN(parsed)) {
      return void 0;
    }
    return Math.max(0, parsed - Date.now());
  }
  function createFetchTransport() {
    const fetchImpl = getFetchSource();
    return async (request) => {
      var _a, _b;
      if (fetchImpl === null) {
        throw new Error("fetch unavailable");
      }
      const response = await fetchImpl(request.endpoint, {
        method: "POST",
        headers: request.headers,
        body: buildBrowserTransportRequestBody(request.endpoint, request.events)
      });
      const retryAfterMs = parseRetryAfter((_b = (_a = response.headers) == null ? void 0 : _a.get("Retry-After")) != null ? _b : null);
      return {
        status: response.status,
        body: typeof response.json === "function" ? await response.json() : void 0,
        ...retryAfterMs === void 0 ? {} : { retry_after_ms: retryAfterMs }
      };
    };
  }
  function buildBrowserTransportRequestBody(endpoint, events) {
    if (isAbsoluteHttpUrl(endpoint)) {
      return JSON.stringify({ events });
    }
    return JSON.stringify({ batch: events });
  }
  function isAbsoluteHttpUrl(value) {
    try {
      const parsed = new URL(value);
      return parsed.protocol === "http:" || parsed.protocol === "https:";
    } catch {
      return false;
    }
  }
  function isValidRelayEndpointPath(value) {
    if (!value.startsWith("/") || value.startsWith("//")) {
      return false;
    }
    try {
      const parsed = new URL(value, "https://debugbundle.local");
      return parsed.origin === "https://debugbundle.local";
    } catch {
      return false;
    }
  }
  function resolveBrowserTransport(input) {
    var _a, _b;
    const endpoint = (_a = input.endpoint) == null ? void 0 : _a.trim();
    const projectToken = (_b = input.projectToken) == null ? void 0 : _b.trim();
    if (endpoint !== void 0 && endpoint.length > 0) {
      if (isAbsoluteHttpUrl(endpoint)) {
        if (projectToken === void 0 || projectToken.length === 0) {
          return {
            mode: "disabled",
            endpoint: null,
            projectToken: null
          };
        }
        return {
          mode: "direct",
          endpoint,
          projectToken
        };
      }
      if (isValidRelayEndpointPath(endpoint)) {
        return {
          mode: "relay",
          endpoint,
          projectToken: null
        };
      }
      return {
        mode: "disabled",
        endpoint: null,
        projectToken: null
      };
    }
    if (projectToken !== void 0 && projectToken.length > 0) {
      return {
        mode: "direct",
        endpoint: DEFAULT_ENDPOINT,
        projectToken
      };
    }
    return {
      mode: "disabled",
      endpoint: null,
      projectToken: null
    };
  }
  function normalizeBoolean(value, fallback) {
    return typeof value === "boolean" ? value : fallback;
  }
  function normalizeNetworkPatterns(value) {
    if (!Array.isArray(value)) {
      return [];
    }
    return value.filter((pattern) => typeof pattern === "string");
  }
  function normalizeStringPatterns(value) {
    if (!Array.isArray(value)) {
      return [];
    }
    return value.filter((pattern) => typeof pattern === "string");
  }
  function normalizeTracePropagationTargets(value) {
    return normalizeNetworkPatterns(value);
  }
  function normalizeNetworkFilter(value) {
    const statusCodes = Array.isArray(value == null ? void 0 : value.statusCodes) ? value.statusCodes.filter((statusCode) => typeof statusCode === "number" && Number.isFinite(statusCode)).map((statusCode) => Math.trunc(statusCode)) : [400, 599];
    return {
      urlPatterns: normalizeStringPatterns(value == null ? void 0 : value.urlPatterns),
      urlDenyPatterns: normalizeStringPatterns(value == null ? void 0 : value.urlDenyPatterns),
      statusCodes: statusCodes.length > 0 ? statusCodes : [400, 599],
      minResponseTime: typeof (value == null ? void 0 : value.minResponseTime) === "number" && Number.isFinite(value.minResponseTime) && value.minResponseTime > 0 ? Math.trunc(value.minResponseTime) : null
    };
  }
  function deriveSdkConfigEndpoint(endpoint) {
    if (endpoint.endsWith("/v1/events")) {
      return `${endpoint.slice(0, -"/events".length)}/sdk/config`;
    }
    if (endpoint.endsWith("/events")) {
      return `${endpoint.slice(0, -"/events".length)}/sdk/config`;
    }
    return endpoint;
  }
  function asRecord(value) {
    if (value === null || typeof value !== "object" || Array.isArray(value)) {
      return null;
    }
    return value;
  }
  function asString(value) {
    return typeof value === "string" && value.length > 0 ? value : null;
  }
  function parseRemoteProbeDirective(value, nowMs) {
    const record = asRecord(value);
    if (record === null) {
      return null;
    }
    const activationId = asString(record["activation_id"]);
    const labelPattern = asString(record["label_pattern"]);
    const service = asString(record["service"]);
    const environment = asString(record["environment"]);
    const expiresAt = asString(record["expires_at"]);
    const triggerExpiresAt = asString(record["trigger_expires_at"]);
    if (activationId === null || labelPattern === null || service === null || environment === null || expiresAt === null || Number.isNaN(Date.parse(expiresAt))) {
      return null;
    }
    if (Date.parse(expiresAt) <= nowMs) {
      return null;
    }
    return {
      activationId,
      labelPattern,
      service,
      environment,
      expiresAt,
      triggerExpiresAt: triggerExpiresAt !== null && !Number.isNaN(Date.parse(triggerExpiresAt)) ? triggerExpiresAt : null
    };
  }
  function parseRemoteProbeConfigPayload(payload, nowMs) {
    const record = asRecord(payload);
    if (record === null) {
      return null;
    }
    const capturePolicy = asRecord(record["capture_policy"]);
    const immediateClientErrorStatuses = capturePolicy !== null && Array.isArray(capturePolicy["immediate_client_error_statuses"]) ? capturePolicy["immediate_client_error_statuses"].filter((entry) => typeof entry === "number" && Number.isInteger(entry) && entry >= 400 && entry <= 499).sort((left, right) => left - right) : [];
    const requestFailurePreset = capturePolicy !== null && isBrowserCapturePreset(capturePolicy["preset"]) ? capturePolicy["preset"] : DEFAULT_REQUEST_FAILURE_PRESET;
    const requestCaptureEvents = capturePolicy !== null && isBrowserCaptureRequestEvents(capturePolicy["capture_request_events"]) ? capturePolicy["capture_request_events"] : DEFAULT_REQUEST_CAPTURE_EVENTS;
    return {
      probesEnabled: record["probes_enabled"] === true,
      remoteProbesEnabled: record["remote_probes_enabled"] === true,
      directives: Array.isArray(record["active_probes"]) ? record["active_probes"].map((directive) => parseRemoteProbeDirective(directive, nowMs)).filter((directive) => directive !== null) : [],
      triggerTokenKey: asString(record["trigger_token_key"]),
      requestFailurePreset,
      requestCaptureEvents,
      immediateClientErrorStatuses: immediateClientErrorStatuses.length === 0 ? [...DEFAULT_IMMEDIATE_CLIENT_ERROR_STATUSES] : Array.from(new Set(immediateClientErrorStatuses))
    };
  }
  function isBrowserCapturePreset(value) {
    return value === "minimal" || value === "balanced" || value === "investigative";
  }
  function isBrowserCaptureRequestEvents(value) {
    return value === "off" || value === "failures_only" || value === "filtered" || value === "all";
  }
  function parseIngestionProbeDirectives(payload, nowMs) {
    const record = asRecord(payload);
    const directivesRecord = record === null ? null : asRecord(record["probe_directives"]);
    if (directivesRecord === null || !Array.isArray(directivesRecord["active_probes"])) {
      return null;
    }
    return directivesRecord["active_probes"].map((directive) => parseRemoteProbeDirective(directive, nowMs)).filter((directive) => directive !== null);
  }
  function stringifyConsoleArgs(args) {
    return args.map((arg) => {
      if (typeof arg === "string") {
        return arg;
      }
      if (arg instanceof Error) {
        return arg.message;
      }
      try {
        return JSON.stringify(arg);
      } catch {
        return String(arg);
      }
    }).join(" ").trim();
  }
  function matchesBrowserPattern(value, pattern) {
    return value.includes(pattern);
  }
  function shouldInjectTraceHeader(url, tracePropagationTargets = []) {
    const locationSource = getLocationSource();
    const locationHref = typeof (locationSource == null ? void 0 : locationSource.href) === "string" && locationSource.href.length > 0 ? locationSource.href : null;
    let resolvedUrl;
    try {
      resolvedUrl = locationHref !== null ? new URL(url, locationHref) : new URL(url);
    } catch {
      return false;
    }
    if (resolvedUrl.protocol !== "http:" && resolvedUrl.protocol !== "https:") {
      return false;
    }
    if (!isAbsoluteHttpUrl(url)) {
      return true;
    }
    if (locationHref !== null) {
      try {
        const currentUrl = new URL(locationHref);
        if (resolvedUrl.origin === currentUrl.origin) {
          return true;
        }
      } catch {
      }
    }
    return tracePropagationTargets.some((pattern) => matchesBrowserPattern(resolvedUrl.href, pattern));
  }
  function matchesStatusCodeFilter(statusCode, filter) {
    if (filter.includes(statusCode)) {
      return true;
    }
    if (filter.length === 2) {
      const [start, end] = filter;
      if (start !== void 0 && end !== void 0 && start < end) {
        return statusCode >= start && statusCode <= end;
      }
    }
    return false;
  }
  function createBrowserTraceId() {
    const cryptoSource = getCryptoSource();
    if (typeof (cryptoSource == null ? void 0 : cryptoSource.randomUUID) === "function") {
      return cryptoSource.randomUUID();
    }
    return "xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx".replace(/[xy]/g, (character) => {
      const randomValue = Math.floor(Math.random() * 16);
      const nibble = character === "x" ? randomValue : randomValue & 3 | 8;
      return nibble.toString(16);
    });
  }
  function buildSelector(target) {
    const tagName = typeof target["tagName"] === "string" ? target["tagName"].toLowerCase() : null;
    if (tagName === null) {
      return null;
    }
    const id = typeof target["id"] === "string" && target["id"].length > 0 ? `#${target["id"]}` : "";
    return `${tagName}${id}`;
  }
  function parseBrowserIdentity(userAgent) {
    var _a, _b, _c;
    if (userAgent === null) {
      return { name: "Unknown", version: "0" };
    }
    const chromeMatch = /Chrome\/([\d.]+)/.exec(userAgent);
    if (chromeMatch !== null) {
      return { name: "Chrome", version: (_a = chromeMatch[1]) != null ? _a : "0" };
    }
    const firefoxMatch = /Firefox\/([\d.]+)/.exec(userAgent);
    if (firefoxMatch !== null) {
      return { name: "Firefox", version: (_b = firefoxMatch[1]) != null ? _b : "0" };
    }
    const safariMatch = /Version\/([\d.]+).*Safari/.exec(userAgent);
    if (safariMatch !== null) {
      return { name: "Safari", version: (_c = safariMatch[1]) != null ? _c : "0" };
    }
    return { name: "Unknown", version: "0" };
  }
  function parseOsIdentity(userAgent) {
    var _a, _b, _c, _d;
    if (userAgent === null) {
      return { name: null, version: null };
    }
    const macMatch = /Mac OS X ([\d_]+)/.exec(userAgent);
    if (macMatch !== null) {
      return {
        name: "macOS",
        version: ((_a = macMatch[1]) != null ? _a : "").replaceAll("_", ".") || null
      };
    }
    const windowsMatch = /Windows NT ([\d.]+)/.exec(userAgent);
    if (windowsMatch !== null) {
      return { name: "Windows", version: (_b = windowsMatch[1]) != null ? _b : null };
    }
    const androidMatch = /Android ([\d.]+)/.exec(userAgent);
    if (androidMatch !== null) {
      return { name: "Android", version: (_c = androidMatch[1]) != null ? _c : null };
    }
    const iosMatch = /OS ([\d_]+) like Mac OS X/.exec(userAgent);
    if (iosMatch !== null) {
      return {
        name: "iOS",
        version: ((_d = iosMatch[1]) != null ? _d : "").replaceAll("_", ".") || null
      };
    }
    return { name: null, version: null };
  }
  function detectDeviceType(userAgent) {
    if (userAgent === null) {
      return "unknown";
    }
    if (/Tablet|iPad/i.test(userAgent)) {
      return "tablet";
    }
    if (/Mobile|Android|iPhone/i.test(userAgent)) {
      return "mobile";
    }
    return "desktop";
  }

  // node_modules/.pnpm/@debugbundle+sdk-browser@0.1.8/node_modules/@debugbundle/sdk-browser/dist/hooks.js
  var MUTATING_METHODS = /* @__PURE__ */ new Set(["POST", "PUT", "PATCH", "DELETE"]);
  var INTERESTING_RESPONSE_HEADERS = [
    "content-type",
    "x-debugbundle-trace-id",
    "retry-after",
    "x-ratelimit-limit",
    "x-ratelimit-remaining",
    "x-ratelimit-reset",
    "x-request-id",
    "www-authenticate",
    "location"
  ];
  function truncateBody(body) {
    if (body.length <= MAX_BODY_CAPTURE_BYTES)
      return body;
    return body.slice(0, MAX_BODY_CAPTURE_BYTES) + "...[truncated]";
  }
  function tryParseJsonBody(raw) {
    try {
      return JSON.parse(raw);
    } catch {
      return raw;
    }
  }
  function extractResponseHeaders(response) {
    if (!response.headers || typeof response.headers.get !== "function")
      return void 0;
    const extracted = {};
    for (const name of INTERESTING_RESPONSE_HEADERS) {
      const value = response.headers.get(name);
      if (value !== null) {
        extracted[name] = value;
      }
    }
    return Object.keys(extracted).length > 0 ? extracted : void 0;
  }
  async function captureResponseBody(response) {
    if (response.status >= 200 && response.status < 300)
      return void 0;
    if (typeof response.clone !== "function" || typeof response.text !== "function")
      return void 0;
    try {
      const cloned = response.clone();
      if (typeof cloned.text !== "function")
        return void 0;
      const text = await cloned.text();
      if (text.length === 0)
        return void 0;
      return truncateBody(text);
    } catch {
      return void 0;
    }
  }
  function captureRequestBody(init) {
    const method = (typeof init.method === "string" ? init.method : "GET").toUpperCase();
    if (!MUTATING_METHODS.has(method))
      return void 0;
    if (typeof init.body !== "string" || init.body.length === 0)
      return void 0;
    return tryParseJsonBody(truncateBody(init.body));
  }
  function buildNetworkBreadcrumbData(url, method, statusCode, durationMs, callerTrace, extras) {
    const data = {
      url,
      method,
      status_code: statusCode,
      duration_ms: durationMs
    };
    if (callerTrace.length > 0)
      data["caller_trace"] = callerTrace;
    if (extras.responseBody !== void 0) {
      data["response_body"] = tryParseJsonBody(extras.responseBody);
    }
    if (extras.requestBody !== void 0) {
      data["request_body"] = extras.requestBody;
    }
    if (extras.responseHeaders !== void 0) {
      data["response_headers"] = extras.responseHeaders;
    }
    if (extras.responseContentLength !== void 0) {
      data["response_content_length"] = extras.responseContentLength;
    }
    const meta = extras.requestMetadata;
    if ((meta == null ? void 0 : meta.operation) !== void 0)
      data["operation"] = meta.operation;
    if ((meta == null ? void 0 : meta.initiator) !== void 0)
      data["initiator"] = meta.initiator;
    if ((meta == null ? void 0 : meta.feature) !== void 0)
      data["feature"] = meta.feature;
    return data;
  }
  function installConsoleHook(config2, addBreadcrumb) {
    const consoleSource = getConsoleSource();
    if (config2 === null || consoleSource === null || config2.captureConsole !== true) {
      return {
        originalConsoleError: null,
        originalConsoleWarn: null
      };
    }
    let originalConsoleError = null;
    let originalConsoleWarn = null;
    if (typeof consoleSource.error === "function") {
      originalConsoleError = consoleSource.error.bind(consoleSource);
      consoleSource.error = (...args) => {
        originalConsoleError == null ? void 0 : originalConsoleError(...args);
        addBreadcrumb({
          ts: (/* @__PURE__ */ new Date()).toISOString(),
          breadcrumb_type: "console_log",
          data: {
            level: "error",
            message: stringifyConsoleArgs(args)
          }
        });
      };
    }
    if (typeof consoleSource.warn === "function") {
      originalConsoleWarn = consoleSource.warn.bind(consoleSource);
      consoleSource.warn = (...args) => {
        originalConsoleWarn == null ? void 0 : originalConsoleWarn(...args);
        addBreadcrumb({
          ts: (/* @__PURE__ */ new Date()).toISOString(),
          breadcrumb_type: "console_log",
          data: {
            level: "warning",
            message: stringifyConsoleArgs(args)
          }
        });
      };
    }
    return {
      originalConsoleError,
      originalConsoleWarn
    };
  }
  function installNetworkHook(config2, addBreadcrumb, captureRequestFailure, shouldCaptureNetworkRequest, getCurrentRoute) {
    const fetchSource = getFetchSource();
    const xmlHttpRequestConstructor = getXmlHttpRequestConstructor();
    if (config2 === null || fetchSource === null && xmlHttpRequestConstructor === null) {
      return {
        originalFetch: null,
        originalXmlHttpRequest: null
      };
    }
    const configEndpoint = deriveSdkConfigEndpoint(config2.endpoint);
    if (fetchSource !== null) {
      globalThis["fetch"] = async (input, init) => {
        var _a;
        const inputInit = init != null ? init : {};
        const { debugbundle: requestMetadata, headers: requestHeaders, ...forwardedInit } = inputInit;
        const callerTrace = captureCallerTrace(1, 5);
        const injectTraceHeader = shouldInjectTraceHeader(input, config2.tracePropagationTargets);
        const traceId = injectTraceHeader ? createBrowserTraceId() : null;
        const startedAt = Date.now();
        const response = await fetchSource(input, {
          ...forwardedInit,
          headers: {
            ...requestHeaders != null ? requestHeaders : {},
            ...traceId === null ? {} : { "X-DebugBundle-Trace-Id": traceId }
          }
        });
        const durationMs = Date.now() - startedAt;
        const shouldCaptureNetworkBreadcrumb = config2.captureNetwork === true && input !== config2.endpoint && input !== configEndpoint && shouldCaptureNetworkRequest(input, response.status, durationMs);
        if (shouldCaptureNetworkBreadcrumb || injectTraceHeader && response.status >= 400) {
          const responseBody = await captureResponseBody(response);
          const requestBody = captureRequestBody(inputInit);
          const responseHeaders = extractResponseHeaders(response);
          const contentLengthHeader = (_a = response.headers) == null ? void 0 : _a.get("content-length");
          const responseContentLength = contentLengthHeader !== null && contentLengthHeader !== void 0 ? parseInt(contentLengthHeader, 10) : void 0;
          const breadcrumb = {
            ts: (/* @__PURE__ */ new Date()).toISOString(),
            breadcrumb_type: "network_request",
            route: getCurrentRoute(),
            data: buildNetworkBreadcrumbData(input, typeof inputInit.method === "string" ? inputInit.method : "GET", response.status, durationMs, callerTrace, {
              requestMetadata,
              responseBody,
              requestBody,
              responseHeaders,
              responseContentLength: Number.isFinite(responseContentLength) ? responseContentLength : void 0
            })
          };
          if (shouldCaptureNetworkBreadcrumb) {
            addBreadcrumb(breadcrumb);
          }
          if (injectTraceHeader && response.status >= 400) {
            captureRequestFailure(breadcrumb);
          }
        }
        return response;
      };
    }
    if (xmlHttpRequestConstructor !== null) {
      const activeConfig = config2;
      const activeXmlHttpRequestConstructor = xmlHttpRequestConstructor;
      const captureXmlHttpRequest = (url, method, statusCode, durationMs) => {
        const isFirstParty = shouldInjectTraceHeader(url, activeConfig.tracePropagationTargets);
        const shouldCaptureNetworkBreadcrumb = activeConfig.captureNetwork === true && url !== activeConfig.endpoint && url !== configEndpoint && shouldCaptureNetworkRequest(url, statusCode, durationMs);
        if (shouldCaptureNetworkBreadcrumb || isFirstParty && statusCode >= 400) {
          const breadcrumb = {
            ts: (/* @__PURE__ */ new Date()).toISOString(),
            breadcrumb_type: "network_request",
            route: getCurrentRoute(),
            data: {
              url,
              method,
              status_code: statusCode,
              duration_ms: durationMs
            }
          };
          if (shouldCaptureNetworkBreadcrumb) {
            addBreadcrumb(breadcrumb);
          }
          if (isFirstParty && statusCode >= 400) {
            captureRequestFailure(breadcrumb);
          }
        }
      };
      globalThis["XMLHttpRequest"] = class WrappedXmlHttpRequest {
        constructor() {
          const request = new activeXmlHttpRequestConstructor();
          let method = "GET";
          let url = "";
          let startedAt = 0;
          const originalOpen = request.open.bind(request);
          request.open = (nextMethod, nextUrl, async) => {
            method = nextMethod;
            url = nextUrl;
            originalOpen(nextMethod, nextUrl, async);
            if (shouldInjectTraceHeader(nextUrl, activeConfig.tracePropagationTargets)) {
              request.setRequestHeader("X-DebugBundle-Trace-Id", createBrowserTraceId());
            }
          };
          const originalSend = request.send.bind(request);
          request.send = (body) => {
            startedAt = Date.now();
            const onLoadEnd = () => {
              request.removeEventListener("loadend", onLoadEnd);
              const statusCode = typeof request.status === "number" ? request.status : 0;
              const durationMs = Date.now() - startedAt;
              captureXmlHttpRequest(url, method, statusCode, durationMs);
            };
            request.addEventListener("loadend", onLoadEnd);
            originalSend(body);
          };
          return request;
        }
      };
    }
    return {
      originalFetch: fetchSource,
      originalXmlHttpRequest: xmlHttpRequestConstructor
    };
  }
  function collectDeviceInfo() {
    var _a, _b, _c, _d, _e;
    const navigatorObject = getNavigatorSource();
    const screenObject = getScreenSource();
    const windowSource = getWindowSource();
    const userAgent = (_a = navigatorObject == null ? void 0 : navigatorObject.userAgent) != null ? _a : null;
    const browser = parseBrowserIdentity(userAgent);
    const matchMedia = getMatchMedia();
    const colorSchemePreference = matchMedia !== null ? matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light" : null;
    return {
      user_agent: userAgent,
      os: parseOsIdentity(userAgent),
      browser,
      device_type: detectDeviceType(userAgent),
      screen: {
        width: typeof (screenObject == null ? void 0 : screenObject.width) === "number" ? screenObject.width : 0,
        height: typeof (screenObject == null ? void 0 : screenObject.height) === "number" ? screenObject.height : 0
      },
      viewport: {
        width: typeof (windowSource == null ? void 0 : windowSource.innerWidth) === "number" ? (_b = windowSource.innerWidth) != null ? _b : 0 : typeof (screenObject == null ? void 0 : screenObject.width) === "number" ? screenObject.width : 0,
        height: typeof (windowSource == null ? void 0 : windowSource.innerHeight) === "number" ? (_c = windowSource.innerHeight) != null ? _c : 0 : typeof (screenObject == null ? void 0 : screenObject.height) === "number" ? screenObject.height : 0
      },
      device_pixel_ratio: typeof (windowSource == null ? void 0 : windowSource.devicePixelRatio) === "number" ? (_d = windowSource.devicePixelRatio) != null ? _d : null : null,
      touch_capable: typeof (navigatorObject == null ? void 0 : navigatorObject.maxTouchPoints) === "number" ? navigatorObject.maxTouchPoints > 0 : null,
      language: (_e = navigatorObject == null ? void 0 : navigatorObject.language) != null ? _e : null,
      connection_type: typeof (navigatorObject == null ? void 0 : navigatorObject.connection) === "object" && navigatorObject.connection !== null ? typeof navigatorObject.connection.effectiveType === "string" ? navigatorObject.connection.effectiveType : null : null,
      color_scheme_preference: colorSchemePreference
    };
  }

  // node_modules/.pnpm/@debugbundle+sdk-browser@0.1.8/node_modules/@debugbundle/sdk-browser/dist/suppression.js
  var DUPLICATE_WINDOW_MS = 3e4;
  var LOOP_WINDOW_MS = 2e3;
  var LOOP_THRESHOLD = 10;
  var LOOP_RESET_AFTER_MS = 6e4;
  var LOOP_CHECKPOINT_MS = 3e4;
  var MAX_NORMAL_EVENTS_PER_WINDOW = 3;
  function createState(nowMs) {
    return {
      windowStartedAtMs: nowMs,
      emittedCount: 0,
      pendingSuppressedCount: 0,
      pendingFirstSeenAtMs: null,
      pendingLastSeenAtMs: null,
      lastAggregateEmittedAtMs: null,
      loopWindowStartedAtMs: nowMs,
      loopHitCount: 0,
      suppressionMode: false,
      lastSeenAtMs: nowMs
    };
  }
  function resetState(state, nowMs) {
    Object.assign(state, createState(nowMs));
  }
  function markSuppressed(state, nowMs) {
    if (state.pendingSuppressedCount === 0) {
      state.pendingFirstSeenAtMs = state.windowStartedAtMs;
    }
    state.pendingSuppressedCount += 1;
    state.pendingLastSeenAtMs = nowMs;
  }
  function buildFingerprint(key) {
    let hash = 2166136261;
    for (let index = 0; index < key.length; index += 1) {
      hash ^= key.charCodeAt(index);
      hash = Math.imul(hash, 16777619);
    }
    return (hash >>> 0).toString(16).padStart(8, "0");
  }
  var EventSuppressionTracker = class {
    constructor() {
      __publicField(this, "states", /* @__PURE__ */ new Map());
    }
    reset() {
      this.states.clear();
    }
    shouldCapture(key, nowMs) {
      var _a;
      const state = (_a = this.states.get(key)) != null ? _a : createState(nowMs);
      this.states.set(key, state);
      if (state.suppressionMode && nowMs - state.lastSeenAtMs >= LOOP_RESET_AFTER_MS) {
        resetState(state, nowMs);
      }
      if (nowMs - state.windowStartedAtMs >= DUPLICATE_WINDOW_MS) {
        state.windowStartedAtMs = nowMs;
        state.emittedCount = 0;
      }
      if (nowMs - state.loopWindowStartedAtMs >= LOOP_WINDOW_MS) {
        state.loopWindowStartedAtMs = nowMs;
        state.loopHitCount = 0;
      }
      state.loopHitCount += 1;
      state.lastSeenAtMs = nowMs;
      if (state.loopHitCount > LOOP_THRESHOLD) {
        state.suppressionMode = true;
      }
      if (state.suppressionMode) {
        markSuppressed(state, nowMs);
        return false;
      }
      if (state.emittedCount < MAX_NORMAL_EVENTS_PER_WINDOW) {
        state.emittedCount += 1;
        return true;
      }
      markSuppressed(state, nowMs);
      return false;
    }
    drainAggregates(nowMs) {
      const aggregates = [];
      for (const [key, state] of this.states.entries()) {
        if (state.pendingSuppressedCount === 0 || state.pendingFirstSeenAtMs === null || state.pendingLastSeenAtMs === null) {
          continue;
        }
        if (state.suppressionMode && state.lastAggregateEmittedAtMs !== null && nowMs - state.lastAggregateEmittedAtMs < LOOP_CHECKPOINT_MS) {
          continue;
        }
        aggregates.push({
          fingerprint: buildFingerprint(key),
          suppressedCount: state.pendingSuppressedCount,
          firstSeen: new Date(state.pendingFirstSeenAtMs).toISOString(),
          lastSeen: new Date(state.pendingLastSeenAtMs).toISOString(),
          windowSeconds: DUPLICATE_WINDOW_MS / 1e3
        });
        state.pendingSuppressedCount = 0;
        state.pendingFirstSeenAtMs = null;
        state.pendingLastSeenAtMs = null;
        state.lastAggregateEmittedAtMs = nowMs;
        if (!state.suppressionMode && nowMs - state.lastSeenAtMs >= LOOP_RESET_AFTER_MS) {
          this.states.delete(key);
        }
      }
      return aggregates;
    }
  };

  // node_modules/.pnpm/@debugbundle+sdk-browser@0.1.8/node_modules/@debugbundle/sdk-browser/dist/trigger-token.js
  var PROBE_TRIGGER_TOKEN_PREFIX = "dbundle_probe_";
  function decodeBase64Url(segment) {
    try {
      if (typeof Buffer !== "undefined") {
        return Buffer.from(segment, "base64url").toString("utf8");
      }
      const normalized = segment.replace(/-/g, "+").replace(/_/g, "/");
      const padded = normalized.padEnd(Math.ceil(normalized.length / 4) * 4, "=");
      const binary = globalThis.atob(padded);
      const bytes = Uint8Array.from(binary, (character) => character.charCodeAt(0));
      return new TextDecoder().decode(bytes);
    } catch {
      return null;
    }
  }
  function decodeBase64UrlBytes(segment) {
    try {
      if (typeof Buffer !== "undefined") {
        return new Uint8Array(Buffer.from(segment, "base64url"));
      }
      const normalized = segment.replace(/-/g, "+").replace(/_/g, "/");
      const padded = normalized.padEnd(Math.ceil(normalized.length / 4) * 4, "=");
      const binary = globalThis.atob(padded);
      return Uint8Array.from(binary, (character) => character.charCodeAt(0));
    } catch {
      return null;
    }
  }
  function parsePayload(payloadSegment) {
    const decoded = decodeBase64Url(payloadSegment);
    if (decoded === null) {
      return null;
    }
    try {
      const parsed = JSON.parse(decoded);
      if (typeof parsed.activation_id !== "string" || typeof parsed.label_pattern !== "string" || typeof parsed.service !== "string" || typeof parsed.environment !== "string" || typeof parsed.trigger_expires_at !== "string") {
        return null;
      }
      if (Number.isNaN(Date.parse(parsed.trigger_expires_at))) {
        return null;
      }
      return parsed;
    } catch {
      return null;
    }
  }
  async function hasValidSignature(payloadSegment, signatureSegment, triggerTokenKey) {
    var _a;
    const subtle = (_a = globalThis.crypto) == null ? void 0 : _a.subtle;
    if (subtle === void 0 || typeof subtle.verify !== "function") {
      return false;
    }
    const signatureBytes = decodeBase64UrlBytes(signatureSegment);
    if (signatureBytes === null) {
      return false;
    }
    const signatureBuffer = Uint8Array.from(signatureBytes).buffer;
    const key = await subtle.importKey("raw", new TextEncoder().encode(triggerTokenKey), { name: "HMAC", hash: "SHA-256" }, false, ["verify"]);
    return subtle.verify("HMAC", key, signatureBuffer, new TextEncoder().encode(payloadSegment));
  }
  async function validateBrowserTriggerToken(input) {
    if (input.triggerTokenKey === null || !input.token.startsWith(PROBE_TRIGGER_TOKEN_PREFIX)) {
      return null;
    }
    const encoded = input.token.slice(PROBE_TRIGGER_TOKEN_PREFIX.length);
    const separatorIndex = encoded.indexOf(".");
    if (separatorIndex <= 0 || separatorIndex === encoded.length - 1) {
      return null;
    }
    const payloadSegment = encoded.slice(0, separatorIndex);
    const signatureSegment = encoded.slice(separatorIndex + 1);
    if (!await hasValidSignature(payloadSegment, signatureSegment, input.triggerTokenKey)) {
      return null;
    }
    const payload = parsePayload(payloadSegment);
    if (payload === null || Date.parse(payload.trigger_expires_at) <= input.nowMs) {
      return null;
    }
    return {
      activationId: payload.activation_id,
      labelPattern: payload.label_pattern,
      service: payload.service,
      environment: payload.environment,
      expiresAt: payload.trigger_expires_at,
      triggerExpiresAt: payload.trigger_expires_at
    };
  }

  // node_modules/.pnpm/@debugbundle+sdk-browser@0.1.8/node_modules/@debugbundle/sdk-browser/dist/index.js
  var DEFAULT_REQUEST_FAILURE_PRESET2 = "balanced";
  var DEFAULT_REQUEST_CAPTURE_EVENTS2 = "failures_only";
  var DEFAULT_IMMEDIATE_CLIENT_ERROR_STATUSES2 = [];
  function createInitialRemoteProbeState() {
    return {
      probesEnabled: false,
      remoteProbesEnabled: false,
      directives: [],
      triggerTokenKey: null,
      requestFailurePreset: DEFAULT_REQUEST_FAILURE_PRESET2,
      requestCaptureEvents: DEFAULT_REQUEST_CAPTURE_EVENTS2,
      immediateClientErrorStatuses: [...DEFAULT_IMMEDIATE_CLIENT_ERROR_STATUSES2]
    };
  }
  var BALANCED_IMMEDIATE_REQUEST_STATUSES2 = /* @__PURE__ */ new Set([408, 423, 424, 425, 429]);
  var INVESTIGATIVE_IMMEDIATE_REQUEST_STATUSES2 = /* @__PURE__ */ new Set([...BALANCED_IMMEDIATE_REQUEST_STATUSES2, 409]);
  var BALANCED_STANDARD_ANOMALY_STATUSES2 = /* @__PURE__ */ new Set([401, 403, 404, 409, 422]);
  var BALANCED_HIGH_VOLUME_ANOMALY_STATUSES2 = /* @__PURE__ */ new Set([400, 410]);
  var INVESTIGATIVE_ANOMALY_STATUSES2 = /* @__PURE__ */ new Set([...BALANCED_STANDARD_ANOMALY_STATUSES2, ...BALANCED_HIGH_VOLUME_ANOMALY_STATUSES2]);
  function isImmediateRequestIncidentStatus(statusCode, preset, immediateClientErrorStatuses = []) {
    if (!Number.isFinite(statusCode)) {
      return false;
    }
    if (statusCode >= 500) {
      return true;
    }
    if (immediateClientErrorStatuses.includes(statusCode)) {
      return true;
    }
    if (preset === "investigative") {
      return INVESTIGATIVE_IMMEDIATE_REQUEST_STATUSES2.has(statusCode);
    }
    if (preset === "balanced") {
      return BALANCED_IMMEDIATE_REQUEST_STATUSES2.has(statusCode);
    }
    return false;
  }
  function isRequestAnomalyCandidateStatus(statusCode, preset) {
    if (!Number.isFinite(statusCode) || statusCode < 400 || statusCode >= 500) {
      return false;
    }
    if (preset === "investigative") {
      return INVESTIGATIVE_ANOMALY_STATUSES2.has(statusCode);
    }
    if (preset === "balanced") {
      return BALANCED_STANDARD_ANOMALY_STATUSES2.has(statusCode) || BALANCED_HIGH_VOLUME_ANOMALY_STATUSES2.has(statusCode);
    }
    return false;
  }
  function shouldCaptureRequestStatus(statusCode, preset, policy, immediateClientErrorStatuses = []) {
    if (isImmediateRequestIncidentStatus(statusCode, preset, immediateClientErrorStatuses)) {
      return true;
    }
    if (policy === "all") {
      return Number.isFinite(statusCode) && statusCode >= 400;
    }
    if (policy === "failures_only") {
      return isRequestAnomalyCandidateStatus(statusCode, preset);
    }
    return false;
  }
  var BrowserSdk = class {
    constructor() {
      __publicField(this, "config", null);
      __publicField(this, "bufferedEvents", []);
      __publicField(this, "breadcrumbs", []);
      __publicField(this, "persistentContext", {});
      __publicField(this, "deviceInfo", null);
      __publicField(this, "flushPromise", null);
      __publicField(this, "flushTimer", null);
      __publicField(this, "nextRetryAt", null);
      __publicField(this, "_lastEventAt", null);
      __publicField(this, "_consecutiveFailures", 0);
      __publicField(this, "authRejected", false);
      __publicField(this, "registeredListeners", []);
      __publicField(this, "originalPushState", null);
      __publicField(this, "originalReplaceState", null);
      __publicField(this, "originalFetch", null);
      __publicField(this, "originalXmlHttpRequest", null);
      __publicField(this, "originalConsoleError", null);
      __publicField(this, "originalConsoleWarn", null);
      __publicField(this, "sessionSampledIn", true);
      __publicField(this, "sessionEventCount", 0);
      __publicField(this, "probeBuffers", /* @__PURE__ */ new Map());
      __publicField(this, "suppressionTracker", new EventSuppressionTracker());
      __publicField(this, "remoteProbeState", createInitialRemoteProbeState());
      __publicField(this, "pendingTriggerToken", null);
      __publicField(this, "activeTriggerDirective", null);
    }
    get status() {
      if (this.config === null) {
        return "disconnected";
      }
      if (this.authRejected) {
        return "disconnected";
      }
      if (this._consecutiveFailures >= 3) {
        return "disconnected";
      }
      if (this.nextRetryAt !== null) {
        return "degraded";
      }
      return "healthy";
    }
    get lastEventAt() {
      return this._lastEventAt;
    }
    init(config2) {
      var _a, _b, _c, _d, _e, _f;
      this.dispose();
      const enabled = (_a = config2.enabled) != null ? _a : true;
      const resolvedTransport = resolveBrowserTransport({
        endpoint: config2.endpoint,
        projectToken: config2.projectToken
      });
      if (!enabled || resolvedTransport.mode === "disabled" || resolvedTransport.endpoint === null) {
        return;
      }
      this.config = {
        projectToken: resolvedTransport.projectToken,
        environment: ((_b = config2.environment) == null ? void 0 : _b.trim()) || "development",
        service: ((_c = config2.service) == null ? void 0 : _c.trim()) || "browser-app",
        enabled,
        redactFields: (_d = config2.redactFields) != null ? _d : ["password", "secret", "token", "authorization", "cookie", "ssn", "credit_card"],
        tracePropagationTargets: normalizeTracePropagationTargets(config2.tracePropagationTargets),
        sampleRate: normalizeSampleRate(config2.sampleRate, DEFAULT_SAMPLE_RATE),
        batchSize: normalizePositiveNumber(config2.batchSize, DEFAULT_BATCH_SIZE),
        flushInterval: normalizePositiveNumber(config2.flushInterval, DEFAULT_FLUSH_INTERVAL_MS),
        endpoint: resolvedTransport.endpoint,
        logLevel: normalizeLogLevel((_e = config2.logLevel) != null ? _e : DEFAULT_LOG_LEVEL),
        maxBreadcrumbs: normalizePositiveNumber(config2.maxBreadcrumbs, DEFAULT_MAX_BREADCRUMBS),
        breadcrumbsOnErrorOnly: normalizeBoolean(config2.breadcrumbsOnErrorOnly, true),
        captureNetwork: normalizeBoolean(config2.captureNetwork, true),
        captureClicks: normalizeBoolean(config2.captureClicks, true),
        captureRouteChanges: normalizeBoolean(config2.captureRouteChanges, true),
        captureConsole: normalizeBoolean(config2.captureConsole, false),
        networkFilter: normalizeNetworkFilter(config2.networkFilter),
        sessionSampleRate: normalizeSampleRate(config2.sessionSampleRate, DEFAULT_SESSION_SAMPLE_RATE),
        maxEventsPerSession: normalizePositiveNumber(config2.maxEventsPerSession, DEFAULT_MAX_EVENTS_PER_SESSION),
        maxProbeLabels: normalizePositiveNumber(config2.maxProbeLabels, 50),
        maxProbeEntriesPerLabel: normalizePositiveNumber(config2.maxProbeEntriesPerLabel, 10),
        probeFlushOnError: normalizeBoolean(config2.probeFlushOnError, true),
        requestTimeoutMs: normalizePositiveNumber(config2.requestTimeoutMs, DEFAULT_REQUEST_TIMEOUT_MS),
        fetchImpl: getFetchSource(),
        transport: (_f = config2.transport) != null ? _f : createFetchTransport(),
        transportMode: resolvedTransport.mode
      };
      this.authRejected = false;
      this.sessionSampledIn = this.config.sessionSampleRate >= 1 || Math.random() < this.config.sessionSampleRate;
      this.sessionEventCount = 0;
      this.deviceInfo = collectDeviceInfo();
      this.pendingTriggerToken = this.consumeTriggerTokenFromLocation();
      void this.refreshRemoteProbeConfig();
      this.installBrowserHooks();
    }
    captureException(error, context = {}) {
      var _a, _b, _c;
      const config2 = this.config;
      if (config2 === null) {
        return;
      }
      try {
        const normalizedError = normalizeError(error);
        const device = this.deviceInfo;
        const browser = (_a = device == null ? void 0 : device.browser) != null ? _a : { name: "Unknown", version: "0" };
        const breadcrumbs = this.consumeBreadcrumbs();
        const probeData = config2.probeFlushOnError ? this.consumeProbeData() : { version: 1, items: [] };
        const domContext = typeof ((_b = context.target) == null ? void 0 : _b.outerHTML) === "string" && context.target.outerHTML.length > 0 ? {
          mode: "lightweight",
          html_excerpt: context.target.outerHTML
        } : null;
        const event = createEventEnvelope({
          schema_version: SDK_SCHEMA_VERSION,
          event_type: "frontend_exception",
          ...this.getProjectTokenFields(config2),
          sdk_name: SDK_NAME,
          sdk_version: SDK_VERSION,
          service: {
            name: config2.service,
            runtime: "browser",
            framework: null,
            environment: config2.environment
          },
          occurred_at: (/* @__PURE__ */ new Date()).toISOString(),
          correlation: this.createCorrelation(),
          payload: {
            name: normalizedError.name,
            message: normalizedError.message,
            stack: normalizedError.stack,
            route: (_c = context.route) != null ? _c : this.getCurrentRoute(),
            browser,
            breadcrumbs,
            probe_data: probeData,
            device: device === null ? null : {
              user_agent: device.user_agent,
              os: device.os,
              device_type: device.device_type,
              screen: device.screen,
              viewport: device.viewport,
              device_pixel_ratio: device.device_pixel_ratio,
              touch_capable: device.touch_capable,
              language: device.language,
              connection_type: device.connection_type,
              color_scheme_preference: device.color_scheme_preference
            },
            dom_context: domContext
          }
        });
        this.removeEmptyProjectToken(event, config2);
        this.enqueueEvent(event);
      } catch {
        return;
      }
    }
    captureError(error, context = {}) {
      this.captureException(error, context);
    }
    captureLog(message, level, context = {}) {
      const config2 = this.config;
      if (config2 === null || !this.shouldCaptureNonExceptionEvent()) {
        return;
      }
      if (LOG_LEVEL_ORDER[level] < LOG_LEVEL_ORDER[config2.logLevel]) {
        return;
      }
      try {
        const attributes = redact({
          ...this.persistentContext,
          ...normalizeUnknownRecord(context)
        }, {
          sensitiveKeys: config2.redactFields
        }).redacted;
        const event = createEventEnvelope({
          schema_version: SDK_SCHEMA_VERSION,
          event_type: "log_event",
          ...this.getProjectTokenFields(config2),
          sdk_name: SDK_NAME,
          sdk_version: SDK_VERSION,
          service: {
            name: config2.service,
            runtime: "browser",
            framework: null,
            environment: config2.environment
          },
          occurred_at: (/* @__PURE__ */ new Date()).toISOString(),
          correlation: this.createCorrelation(),
          payload: {
            level,
            message,
            attributes
          }
        });
        this.removeEmptyProjectToken(event, config2);
        this.enqueueEvent(event);
      } catch {
        return;
      }
    }
    captureRequest(request, response, context) {
      void request;
      void response;
      void context;
    }
    captureMessage(message, level = "info", context = {}) {
      this.captureLog(message, normalizeLogLevel(level), context);
    }
    setContext(key, value) {
      var _a;
      const config2 = this.config;
      if (config2 === null || key.trim().length === 0) {
        return;
      }
      const redacted = redact({ [key]: value }, {
        sensitiveKeys: config2.redactFields
      }).redacted;
      this.persistentContext[key] = (_a = redacted[key]) != null ? _a : null;
    }
    probe(label, data) {
      const config2 = this.config;
      const normalizedLabel = label.trim();
      if (config2 === null || normalizedLabel.length === 0) {
        return;
      }
      try {
        const redacted = redact(this.normalizeProbeInput(data), {
          sensitiveKeys: config2.redactFields
        }).redacted;
        const probeData = normalizeUnknownRecord(redacted);
        this.bufferProbe(normalizedLabel, probeData);
        const matchingDirectives = this.getMatchingRemoteProbeDirectives(normalizedLabel, Date.now());
        if (!this.sessionSampledIn || matchingDirectives.length === 0) {
          return;
        }
        for (const directive of matchingDirectives) {
          this.enqueueEvent(this.createSdkEventEnvelope(config2, {
            schema_version: SDK_SCHEMA_VERSION,
            event_type: "probe_event",
            ...this.getProjectTokenFields(config2),
            sdk_name: SDK_NAME,
            sdk_version: SDK_VERSION,
            service: {
              name: config2.service,
              runtime: "browser",
              framework: null,
              environment: config2.environment
            },
            occurred_at: (/* @__PURE__ */ new Date()).toISOString(),
            correlation: this.createCorrelation(),
            payload: {
              label: normalizedLabel,
              data: probeData,
              activation_id: directive.activationId,
              probe_label_pattern: directive.labelPattern
            }
          }), false);
        }
      } catch {
        return;
      }
    }
    async flush() {
      const config2 = this.config;
      if (config2 === null) {
        return;
      }
      this.enqueueSuppressionAggregates();
      if (this.bufferedEvents.length === 0) {
        return;
      }
      if (this.flushPromise !== null) {
        return this.flushPromise;
      }
      if (this.nextRetryAt !== null && Date.now() < this.nextRetryAt) {
        return;
      }
      if (this.authRejected) {
        return;
      }
      this.clearFlushTimer();
      const events = [...this.bufferedEvents];
      this.flushPromise = (async () => {
        var _a;
        try {
          const response = await config2.transport({
            endpoint: config2.endpoint,
            headers: this.getTransportHeaders(config2),
            events,
            timeout_ms: config2.requestTimeoutMs
          });
          if (response.status >= 200 && response.status < 300) {
            this.updateRemoteProbeStateFromIngestionResponse(response.body);
            this.nextRetryAt = null;
            this._lastEventAt = Date.now();
            this._consecutiveFailures = 0;
            if (this.bufferedEvents === events || this.sameLeadingEvents(events)) {
              this.bufferedEvents.splice(0, events.length);
            }
            return;
          }
          this._consecutiveFailures++;
          if (response.status === 401 || response.status === 403) {
            this.authRejected = true;
            this.nextRetryAt = null;
            this.reportUnauthorizedTransportFailure(response.status, config2.endpoint, response.body);
            this.bufferedEvents = [];
            return;
          }
          if (response.status === 429) {
            this.nextRetryAt = Date.now() + ((_a = response.retry_after_ms) != null ? _a : 1e3);
          }
        } catch {
          this._consecutiveFailures++;
          return;
        } finally {
          this.flushPromise = null;
          if (this.bufferedEvents.length > 0) {
            const retryDelay = this.nextRetryAt === null ? void 0 : Math.max(0, this.nextRetryAt - Date.now());
            this.scheduleFlush(retryDelay);
          }
        }
      })();
      return this.flushPromise;
    }
    dispose() {
      var _a;
      this.clearFlushTimer();
      this.flushPromise = null;
      this.bufferedEvents = [];
      this.breadcrumbs = [];
      this.probeBuffers = /* @__PURE__ */ new Map();
      this.persistentContext = {};
      this.deviceInfo = null;
      this.config = null;
      this.sessionSampledIn = true;
      this.sessionEventCount = 0;
      this.nextRetryAt = null;
      this._lastEventAt = null;
      this._consecutiveFailures = 0;
      this.authRejected = false;
      this.suppressionTracker.reset();
      this.remoteProbeState = createInitialRemoteProbeState();
      this.pendingTriggerToken = null;
      this.activeTriggerDirective = null;
      while (this.registeredListeners.length > 0) {
        (_a = this.registeredListeners.pop()) == null ? void 0 : _a();
      }
      const historySource = getHistorySource();
      if (this.originalPushState !== null && historySource !== null) {
        historySource.pushState = this.originalPushState;
        this.originalPushState = null;
      }
      if (this.originalReplaceState !== null && historySource !== null) {
        historySource.replaceState = this.originalReplaceState;
        this.originalReplaceState = null;
      }
      const consoleSource = getConsoleSource();
      if (consoleSource !== null && this.originalConsoleError !== null) {
        consoleSource.error = this.originalConsoleError;
        this.originalConsoleError = null;
      }
      if (consoleSource !== null && this.originalConsoleWarn !== null) {
        consoleSource.warn = this.originalConsoleWarn;
        this.originalConsoleWarn = null;
      }
      if (this.originalFetch !== null) {
        globalThis["fetch"] = this.originalFetch;
        this.originalFetch = null;
      }
      if (this.originalXmlHttpRequest !== null) {
        globalThis["XMLHttpRequest"] = this.originalXmlHttpRequest;
        this.originalXmlHttpRequest = null;
      }
    }
    reportUnauthorizedTransportFailure(statusCode, endpoint, body) {
      var _a;
      const consoleSource = getConsoleSource();
      if (consoleSource === null) {
        return;
      }
      const bodyRecord = normalizeUnknownRecord(body);
      const errorCode = typeof bodyRecord["error"] === "string" && bodyRecord["error"].length > 0 ? bodyRecord["error"] : null;
      const detail = errorCode === null ? "" : ` (${errorCode})`;
      const message = `DebugBundle browser SDK disabled after ingestion returned ${statusCode} for ${endpoint}. Check the project token or relay configuration${detail}.`;
      if (typeof consoleSource.error === "function") {
        consoleSource.error(message);
        return;
      }
      (_a = consoleSource.warn) == null ? void 0 : _a.call(consoleSource, message);
    }
    installBrowserHooks() {
      const windowSource = getWindowSource();
      if (windowSource !== null) {
        const onPageHide = () => {
          this.flushViaBeacon();
        };
        const onError = (event) => {
          var _a, _b;
          const maybeError = normalizeUnknownRecord(event);
          this.captureException((_b = (_a = maybeError["error"]) != null ? _a : maybeError["message"]) != null ? _b : new Error("Window error"));
        };
        const onUnhandledRejection = (event) => {
          var _a;
          const maybeError = normalizeUnknownRecord(event);
          this.captureException((_a = maybeError["reason"]) != null ? _a : new Error("Unhandled promise rejection"));
        };
        windowSource.addEventListener("pagehide", onPageHide);
        windowSource.addEventListener("error", onError);
        windowSource.addEventListener("unhandledrejection", onUnhandledRejection);
        this.registeredListeners.push(() => windowSource.removeEventListener("pagehide", onPageHide));
        this.registeredListeners.push(() => windowSource.removeEventListener("error", onError));
        this.registeredListeners.push(() => windowSource.removeEventListener("unhandledrejection", onUnhandledRejection));
      }
      const documentSource = getDocumentSource();
      if (documentSource !== null) {
        const onClick = (event) => {
          var _a;
          if (((_a = this.config) == null ? void 0 : _a.captureClicks) !== true) {
            return;
          }
          const target = normalizeUnknownRecord(normalizeUnknownRecord(event)["target"]);
          const selector = buildSelector(target);
          if (selector === null) {
            return;
          }
          this.addBreadcrumb({
            ts: (/* @__PURE__ */ new Date()).toISOString(),
            breadcrumb_type: "click",
            data: {
              selector
            }
          });
        };
        const onSubmit = (event) => {
          var _a;
          const target = normalizeUnknownRecord(normalizeUnknownRecord(event)["target"]);
          const selector = (_a = buildSelector(target)) != null ? _a : "form";
          const elements = Array.isArray(target["elements"]) ? target["elements"] : [];
          const fieldCount = elements.map((entry) => normalizeUnknownRecord(entry)).filter((entry) => typeof entry["name"] === "string" && entry["name"].length > 0).length;
          this.addBreadcrumb({
            ts: (/* @__PURE__ */ new Date()).toISOString(),
            breadcrumb_type: "form_submit",
            data: {
              form: selector,
              field_count: fieldCount
            }
          });
        };
        const onVisibilityChange = () => {
          if (documentSource.visibilityState === "hidden") {
            this.flushViaBeacon();
          }
        };
        documentSource.addEventListener("click", onClick);
        documentSource.addEventListener("submit", onSubmit);
        documentSource.addEventListener("visibilitychange", onVisibilityChange);
        this.registeredListeners.push(() => documentSource.removeEventListener("click", onClick));
        this.registeredListeners.push(() => documentSource.removeEventListener("submit", onSubmit));
        this.registeredListeners.push(() => documentSource.removeEventListener("visibilitychange", onVisibilityChange));
      }
      const historySource = getHistorySource();
      if (historySource !== null) {
        this.originalPushState = historySource.pushState.bind(historySource);
        this.originalReplaceState = historySource.replaceState.bind(historySource);
        historySource.pushState = (state, title, url) => {
          var _a;
          (_a = this.originalPushState) == null ? void 0 : _a.call(this, state, title, url);
          this.captureRouteChange(url);
        };
        historySource.replaceState = (state, title, url) => {
          var _a;
          (_a = this.originalReplaceState) == null ? void 0 : _a.call(this, state, title, url);
          this.captureRouteChange(url);
        };
      }
      const consoleHooks = installConsoleHook(this.config, (breadcrumb) => {
        this.addBreadcrumb(breadcrumb);
      });
      this.originalConsoleError = consoleHooks.originalConsoleError;
      this.originalConsoleWarn = consoleHooks.originalConsoleWarn;
      const networkHooks = installNetworkHook(this.config, (breadcrumb) => {
        this.addBreadcrumb(breadcrumb);
      }, (breadcrumb) => {
        this.captureNetworkRequestFailure(breadcrumb);
      }, (url, statusCode, durationMs) => this.shouldCaptureNetworkRequest(url, statusCode, durationMs), () => this.getCurrentRoute());
      this.originalFetch = networkHooks.originalFetch;
      this.originalXmlHttpRequest = networkHooks.originalXmlHttpRequest;
    }
    createCorrelation() {
      return {
        request_id: null,
        trace_id: null,
        session_id: null,
        user_id_hash: null
      };
    }
    addBreadcrumb(breadcrumb) {
      const config2 = this.config;
      if (config2 === null || this.authRejected || !this.shouldCaptureBreadcrumb()) {
        return;
      }
      if (config2.breadcrumbsOnErrorOnly !== true) {
        this.enqueueEvent(this.createBreadcrumbEvent(breadcrumb));
        return;
      }
      this.breadcrumbs.push(breadcrumb);
      this.sessionEventCount += 1;
      while (this.breadcrumbs.length > config2.maxBreadcrumbs) {
        this.breadcrumbs.shift();
      }
    }
    bufferProbe(label, data) {
      var _a;
      const config2 = this.config;
      if (config2 === null || this.authRejected) {
        return;
      }
      if (!this.probeBuffers.has(label) && this.probeBuffers.size >= config2.maxProbeLabels) {
        return;
      }
      const buffer = (_a = this.probeBuffers.get(label)) != null ? _a : [];
      buffer.push({
        label,
        data,
        timestamp: (/* @__PURE__ */ new Date()).toISOString(),
        activation_id: null
      });
      while (buffer.length > config2.maxProbeEntriesPerLabel) {
        buffer.shift();
      }
      this.probeBuffers.set(label, buffer);
    }
    consumeProbeData() {
      const items = Array.from(this.probeBuffers.values()).flatMap((buffer) => buffer);
      this.probeBuffers.clear();
      return {
        version: 1,
        items
      };
    }
    consumeBreadcrumbs() {
      const breadcrumbs = [...this.breadcrumbs];
      this.breadcrumbs = [];
      return breadcrumbs;
    }
    captureRouteChange(url) {
      var _a;
      if (((_a = this.config) == null ? void 0 : _a.captureRouteChanges) !== true) {
        return;
      }
      const route = typeof url === "string" ? url : url instanceof URL ? url.pathname : this.getCurrentRoute();
      if (route === null) {
        return;
      }
      this.addBreadcrumb({
        ts: (/* @__PURE__ */ new Date()).toISOString(),
        breadcrumb_type: "route_change",
        route,
        data: {
          route
        }
      });
    }
    createBreadcrumbEvent(breadcrumb) {
      var _a;
      const config2 = this.config;
      if (config2 === null) {
        throw new Error("Browser SDK not initialized");
      }
      return this.createSdkEventEnvelope(config2, {
        schema_version: SDK_SCHEMA_VERSION,
        event_type: "frontend_breadcrumb",
        ...this.getProjectTokenFields(config2),
        sdk_name: SDK_NAME,
        sdk_version: SDK_VERSION,
        service: {
          name: config2.service,
          runtime: "browser",
          framework: null,
          environment: config2.environment
        },
        occurred_at: breadcrumb.ts,
        correlation: this.createCorrelation(),
        payload: {
          breadcrumb_type: breadcrumb.breadcrumb_type,
          route: (_a = breadcrumb.route) != null ? _a : this.getCurrentRoute(),
          data: breadcrumb.data
        }
      });
    }
    captureNetworkRequestFailure(breadcrumb) {
      const config2 = this.config;
      if (config2 === null || breadcrumb.breadcrumb_type !== "network_request") {
        return;
      }
      const data = breadcrumb.data;
      const statusCode = typeof data["status_code"] === "number" ? data["status_code"] : 0;
      if (!shouldCaptureRequestStatus(statusCode, this.remoteProbeState.requestFailurePreset, this.remoteProbeState.requestCaptureEvents, this.remoteProbeState.immediateClientErrorStatuses)) {
        return;
      }
      const rawUrl = typeof data["url"] === "string" && data["url"].length > 0 ? data["url"] : "/";
      const method = typeof data["method"] === "string" && data["method"].length > 0 ? data["method"] : "GET";
      const durationMs = typeof data["duration_ms"] === "number" && Number.isFinite(data["duration_ms"]) ? data["duration_ms"] : 0;
      const requestTarget = this.resolveRequestTarget(rawUrl);
      this.enqueueEvent(this.createSdkEventEnvelope(config2, {
        schema_version: SDK_SCHEMA_VERSION,
        event_type: "request_event",
        ...this.getProjectTokenFields(config2),
        sdk_name: SDK_NAME,
        sdk_version: SDK_VERSION,
        service: {
          name: config2.service,
          runtime: "browser",
          framework: null,
          environment: config2.environment
        },
        occurred_at: breadcrumb.ts,
        correlation: this.createCorrelation(),
        payload: {
          method,
          path: requestTarget.path,
          query: requestTarget.query,
          headers: {},
          response_status: statusCode,
          duration_ms: durationMs,
          ...Object.prototype.hasOwnProperty.call(data, "request_body") ? { body: data["request_body"] } : {},
          ...typeof data["response_headers"] === "object" && data["response_headers"] !== null ? { response_headers: data["response_headers"] } : {},
          ...Object.prototype.hasOwnProperty.call(data, "response_body") ? { response_body: data["response_body"] } : {}
        }
      }), false);
    }
    resolveRequestTarget(rawUrl) {
      const locationSource = getLocationSource();
      const baseHref = typeof (locationSource == null ? void 0 : locationSource.href) === "string" && locationSource.href.length > 0 ? locationSource.href : void 0;
      try {
        const parsedUrl = baseHref === void 0 ? new URL(rawUrl) : new URL(rawUrl, baseHref);
        return {
          path: parsedUrl.pathname || "/",
          query: Object.fromEntries(parsedUrl.searchParams.entries())
        };
      } catch {
        return { path: rawUrl.startsWith("/") ? rawUrl : "/", query: {} };
      }
    }
    getCurrentRoute() {
      const locationSource = getLocationSource();
      if (locationSource === null) {
        return null;
      }
      return typeof locationSource.pathname === "string" ? locationSource.pathname : null;
    }
    enqueueEvent(event, countTowardSession = true) {
      if (!this.shouldCaptureBySampleRate(event)) {
        return;
      }
      const suppressionKey = this.buildSuppressionKey(event);
      if (suppressionKey !== null && !this.suppressionTracker.shouldCapture(suppressionKey, Date.now())) {
        this.scheduleFlush();
        return;
      }
      this.enqueueInternalEvent(event, countTowardSession);
    }
    enqueueInternalEvent(event, countTowardSession = true) {
      const config2 = this.config;
      if (config2 === null || this.authRejected) {
        return;
      }
      this.bufferedEvents.push(event);
      if (countTowardSession && event.event_type !== "frontend_exception") {
        this.sessionEventCount += 1;
      }
      if (this.bufferedEvents.length >= config2.batchSize) {
        queueMicrotask(() => {
          void this.flush();
        });
        return;
      }
      this.scheduleFlush();
    }
    buildSuppressionKey(event) {
      var _a, _b;
      if (event.event_type === "frontend_exception") {
        const stackFrame = (_b = (_a = event.payload.stack.split("\n")[1]) == null ? void 0 : _a.trim()) != null ? _b : null;
        return JSON.stringify({
          event_type: event.event_type,
          name: event.payload.name,
          message: event.payload.message,
          stack_frame: stackFrame,
          route: event.payload.route
        });
      }
      if (event.event_type === "log_event") {
        return JSON.stringify({
          event_type: event.event_type,
          level: event.payload.level,
          message: event.payload.message,
          attributes: event.payload.attributes
        });
      }
      if (event.event_type === "request_event") {
        return JSON.stringify({
          event_type: event.event_type,
          method: event.payload.method,
          path: event.payload.path,
          response_status: event.payload.response_status
        });
      }
      return null;
    }
    shouldCaptureBySampleRate(event) {
      const config2 = this.config;
      if (config2 === null) {
        return false;
      }
      if (event.event_type === "frontend_exception" || event.event_type === "error_suppressed" || event.event_type === "request_event" && isImmediateRequestIncidentStatus(event.payload.response_status, this.remoteProbeState.requestFailurePreset, this.remoteProbeState.immediateClientErrorStatuses)) {
        return true;
      }
      return config2.sampleRate >= 1 || Math.random() <= config2.sampleRate;
    }
    scheduleFlush(delayMs) {
      const config2 = this.config;
      if (config2 === null) {
        return;
      }
      if (this.flushTimer !== null) {
        clearTimeout(this.flushTimer);
        this.flushTimer = null;
      }
      this.flushTimer = setTimeout(() => {
        this.flushTimer = null;
        void this.flush();
      }, delayMs != null ? delayMs : config2.flushInterval);
    }
    clearFlushTimer() {
      if (this.flushTimer !== null) {
        clearTimeout(this.flushTimer);
        this.flushTimer = null;
      }
    }
    flushViaBeacon() {
      const config2 = this.config;
      const navigatorSource = getNavigatorSource();
      if (config2 === null || this.bufferedEvents.length === 0 || navigatorSource === null) {
        return;
      }
      const pendingEvents = [...this.bufferedEvents];
      const body = buildBrowserTransportRequestBody(config2.endpoint, pendingEvents);
      const flushViaKeepalive = () => {
        if (config2.fetchImpl === null) {
          void this.flush();
          return;
        }
        void config2.fetchImpl(config2.endpoint, {
          method: "POST",
          headers: this.getTransportHeaders(config2),
          body,
          keepalive: true
        }).then(() => {
          if (this.bufferedEvents === pendingEvents || this.sameLeadingEvents(pendingEvents)) {
            this.bufferedEvents.splice(0, pendingEvents.length);
          }
          this.nextRetryAt = null;
          this.clearFlushTimer();
        }).catch(() => {
          return;
        });
      };
      if (typeof navigatorSource.sendBeacon !== "function") {
        flushViaKeepalive();
        return;
      }
      const beaconBody = typeof Blob === "function" ? new Blob([body], { type: "application/json" }) : body;
      const accepted = navigatorSource.sendBeacon(config2.endpoint, beaconBody);
      if (accepted) {
        this.bufferedEvents = [];
        this.nextRetryAt = null;
        this.clearFlushTimer();
        return;
      }
      flushViaKeepalive();
    }
    sameLeadingEvents(events) {
      if (this.bufferedEvents.length < events.length) {
        return false;
      }
      return events.every((event, index) => {
        var _a;
        return ((_a = this.bufferedEvents[index]) == null ? void 0 : _a.event_id) === event.event_id;
      });
    }
    shouldCaptureNonExceptionEvent() {
      const config2 = this.config;
      if (config2 === null) {
        return false;
      }
      return this.sessionSampledIn && this.sessionEventCount < config2.maxEventsPerSession;
    }
    shouldCaptureBreadcrumb() {
      return this.shouldCaptureNonExceptionEvent();
    }
    getProjectTokenFields(config2) {
      if (config2.projectToken === null) {
        return {};
      }
      return {
        project_token: config2.projectToken
      };
    }
    getTransportHeaders(config2) {
      if (config2.projectToken === null) {
        return {
          "content-type": "application/json"
        };
      }
      return {
        "content-type": "application/json",
        authorization: `Bearer ${config2.projectToken}`
      };
    }
    createSdkEventEnvelope(config2, input) {
      const event = createEventEnvelope(input);
      this.removeEmptyProjectToken(event, config2);
      return event;
    }
    removeEmptyProjectToken(event, config2) {
      if (config2.projectToken !== null) {
        return;
      }
      delete event["project_token"];
    }
    enqueueSuppressionAggregates() {
      const config2 = this.config;
      if (config2 === null) {
        return;
      }
      for (const aggregate of this.suppressionTracker.drainAggregates(Date.now())) {
        this.enqueueInternalEvent(this.createSdkEventEnvelope(config2, {
          schema_version: SDK_SCHEMA_VERSION,
          event_type: "error_suppressed",
          ...this.getProjectTokenFields(config2),
          sdk_name: SDK_NAME,
          sdk_version: SDK_VERSION,
          service: {
            name: config2.service,
            runtime: "browser",
            framework: null,
            environment: config2.environment
          },
          occurred_at: aggregate.lastSeen,
          payload: {
            fingerprint: aggregate.fingerprint,
            suppressed_count: aggregate.suppressedCount,
            window_seconds: aggregate.windowSeconds,
            first_seen: aggregate.firstSeen,
            last_seen: aggregate.lastSeen
          }
        }), false);
      }
    }
    shouldCaptureNetworkRequest(url, statusCode, durationMs) {
      const config2 = this.config;
      if (config2 === null) {
        return false;
      }
      const filter = config2.networkFilter;
      if (filter.urlPatterns.length > 0 && !filter.urlPatterns.some((pattern) => matchesBrowserPattern(url, pattern))) {
        return false;
      }
      if (filter.urlDenyPatterns.some((pattern) => matchesBrowserPattern(url, pattern))) {
        return false;
      }
      if (filter.minResponseTime !== null && durationMs < filter.minResponseTime) {
        return false;
      }
      return matchesStatusCodeFilter(statusCode, filter.statusCodes);
    }
    pruneExpiredRemoteProbeDirectives(nowMs) {
      const directives = this.remoteProbeState.directives.filter((directive) => Date.parse(directive.expiresAt) > nowMs);
      if (this.activeTriggerDirective !== null && Date.parse(this.activeTriggerDirective.expiresAt) <= nowMs) {
        this.activeTriggerDirective = null;
      }
      if (directives.length === this.remoteProbeState.directives.length) {
        return;
      }
      this.remoteProbeState = {
        ...this.remoteProbeState,
        directives
      };
    }
    async refreshRemoteProbeConfig() {
      const config2 = this.config;
      if (config2 === null || config2.fetchImpl === null || config2.transportMode !== "direct" || config2.projectToken === null) {
        return;
      }
      try {
        const response = await config2.fetchImpl(deriveSdkConfigEndpoint(config2.endpoint), {
          method: "GET",
          headers: {
            authorization: `Bearer ${config2.projectToken}`
          }
        });
        if (response.status === 304 || typeof response.json !== "function") {
          return;
        }
        const payload = await response.json();
        const parsed = parseRemoteProbeConfigPayload(payload, Date.now());
        if (parsed !== null) {
          this.remoteProbeState = parsed;
          this.pruneExpiredRemoteProbeDirectives(Date.now());
          await this.activatePendingTriggerTokenIfPossible();
        }
      } catch {
        return;
      }
    }
    updateRemoteProbeStateFromIngestionResponse(payload) {
      const directives = parseIngestionProbeDirectives(payload, Date.now());
      if (directives === null) {
        this.pruneExpiredRemoteProbeDirectives(Date.now());
        return;
      }
      this.remoteProbeState = {
        ...this.remoteProbeState,
        directives
      };
      this.pruneExpiredRemoteProbeDirectives(Date.now());
    }
    consumeTriggerTokenFromLocation() {
      var _a;
      const locationSource = getLocationSource();
      const historySource = getHistorySource();
      const search = typeof (locationSource == null ? void 0 : locationSource.search) === "string" ? locationSource.search : "";
      if (search.length === 0) {
        return null;
      }
      const params = new URLSearchParams(search.startsWith("?") ? search.slice(1) : search);
      const token = params.get("_debug_probe");
      if (token === null || token.length === 0) {
        return null;
      }
      params.delete("_debug_probe");
      const cleanedPath = `${(_a = locationSource == null ? void 0 : locationSource.pathname) != null ? _a : ""}${params.toString().length > 0 ? `?${params.toString()}` : ""}`;
      historySource == null ? void 0 : historySource.replaceState({}, "", cleanedPath);
      return token;
    }
    async activatePendingTriggerTokenIfPossible() {
      if (this.pendingTriggerToken === null) {
        return;
      }
      const directive = await validateBrowserTriggerToken({
        token: this.pendingTriggerToken,
        triggerTokenKey: this.remoteProbeState.triggerTokenKey,
        nowMs: Date.now()
      });
      this.pendingTriggerToken = null;
      this.activeTriggerDirective = directive;
    }
    normalizeProbeInput(data) {
      if (data === null || typeof data !== "object" || Array.isArray(data)) {
        return { value: data };
      }
      return data;
    }
    getMatchingRemoteProbeDirectives(label, nowMs) {
      const config2 = this.config;
      if (config2 === null || this.remoteProbeState.probesEnabled !== true || this.remoteProbeState.remoteProbesEnabled !== true) {
        return [];
      }
      this.pruneExpiredRemoteProbeDirectives(nowMs);
      const activeDirectives = this.activeTriggerDirective === null ? this.remoteProbeState.directives : [...this.remoteProbeState.directives, this.activeTriggerDirective];
      return activeDirectives.filter((directive) => {
        if (directive.service !== "*" && directive.service !== config2.service) {
          return false;
        }
        if (directive.environment !== "*" && directive.environment !== config2.environment) {
          return false;
        }
        return this.matchesProbeLabelPattern(directive.labelPattern, label);
      });
    }
    matchesProbeLabelPattern(pattern, label) {
      if (pattern === "*") {
        return true;
      }
      if (pattern.endsWith(".*")) {
        const prefix = pattern.slice(0, -2);
        return label === prefix || label.startsWith(`${prefix}.`);
      }
      return pattern === label;
    }
  };
  function createDebugBundleBrowserSdk() {
    return new BrowserSdk();
  }

  // assets/src/browser.ts
  var config = window.DebugBundleWordPressConfig;
  if (config && typeof config === "object") {
    try {
      const sdk = createDebugBundleBrowserSdk();
      sdk.init(config);
      if (config.debug === true) {
        window.DebugBundleWordPress = sdk;
      }
    } catch {
    }
  }
})();
