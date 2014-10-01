<?php

namespace Elephander\Exceptionizer;

use ErrorException;

class Error            extends ErrorException {}
class Parse            extends ErrorException {}
class CompileError     extends ErrorException {}
class UserError        extends ErrorException {}
class Warning          extends ErrorException {}
class CoreError        extends ErrorException {}
class CoreWarning      extends ErrorException {}
class CompileWarning   extends ErrorException {}
class UserWarning      extends ErrorException {}
class RecoverableError extends ErrorException {}
