<?php
/**
 * Smarty striphtml outputfilter plugin
 * Remove whitespaces
 *
 * @param string $source
 * @return string
 */
function smarty_outputfilter_striphtml ($source)
{
    return str_replace(["\n", "\r", "\t", "  "], '', $source);
}
