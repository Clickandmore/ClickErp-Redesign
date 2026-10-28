<?php

namespace App\Mcp;

use RuntimeException;

/** خطأ متوقع داخل أداة؛ رسالته تصل للمساعد كما هي (isError=true). */
class McpToolException extends RuntimeException
{
}
