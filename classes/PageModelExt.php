<?php

namespace Contao;

class PageModelExt extends \Model
{
    /**
     * Table name
     * @var string
     */
    protected static $strTable = 'tl_page';

 
	/**
	 * Find an error 410 page by its parent ID
	 *
	 * @param integer $intPid     The parent page's ID
	 * @param array   $arrOptions An optional options array
	 *
	 * @return PageModel|null The model or null if there is no 410 page
	 */
	public static function find410ByPid($intPid, array $arrOptions=array())
	{
		$t = static::$strTable;
		$arrColumns = array("$t.pid=? AND $t.type='error_410'");

		if (!static::isPreviewMode($arrOptions))
		{
			$time = Date::floorToMinute();
			$arrColumns[] = "($t.start='' OR $t.start<='$time') AND ($t.stop='' OR $t.stop>'" . ($time + 60) . "') AND $t.published='1'";
		}

		if (!isset($arrOptions['order']))
		{
			$arrOptions['order'] = "$t.sorting";
		}

		return static::findOneBy($arrColumns, $intPid, $arrOptions);
	}
	

}
?>