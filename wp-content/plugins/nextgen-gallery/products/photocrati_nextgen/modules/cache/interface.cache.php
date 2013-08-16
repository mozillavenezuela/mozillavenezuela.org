<?php

interface I_Cache
{
	function flush_directory($directory, $recursive = TRUE, $regex = NULL);
    function flush_galleries($galleries = array());
}
