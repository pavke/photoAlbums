<?php
/**
 * Модель
 *
 * @package    DIAFAN.CMS
 * @author     FeliciaJess
 * @version    6.0
 * @license    http://www.diafan.ru/license.html
 * @copyright  Copyright (c) 2003-2018 OOO «Диафан» (http://www.diafan.ru/)
 */

if (! defined('DIAFAN'))
{
	$path = __FILE__;
	while(! file_exists($path.'/includes/404.php'))
	{
		$parent = dirname($path);
		if($parent == $path) exit;
		$path = $parent;
	}
	include $path.'/includes/404.php';
}

/**
 * Photo_model
 */
class Photo_model extends Model
{
	/**
	 * Генерирует данные для шаблонной функции: блок альбомов
	 *
	 * @param integer $count количество фотографий
	 * @param array $site_ids страницы сайта
	 * @param array $cat_ids категории
	 * @param string $sort сортировка date - по дате, rand - случайно
	 * @param string $images_variation размер изображений
	 * @param string $tag тег
	 * @return array
	 */
	new public function show_category($count, $site_ids, $cat_ids, $sort, $images_variation, $tag)
	{
		$time = mktime(23, 59, 0, date("m"), date("d"), date("Y"));

		//кеширование
		$cache_meta = array(
			"name"     => "block",
			"cat_ids" => $cat_ids,
			"site_ids" => $site_ids,
			"count"    => $count,
			"lang_id" => _LANG,
			"current"  => ($this->diafan->_site->module == 'photo' && $this->diafan->_route->show ? $this->diafan->_route->show : ''),
			"images_variation" => $images_variation,
			"access" => ($this->diafan->configmodules('where_access_element', 'photo') || $this->diafan->configmodules('where_access_cat', 'photo') ? $this->diafan->_users->role_id : 0),
			"time"     => $time,
			"tag" => $tag,
		);

		if ($sort == "rand" || ! $result = $this->diafan->_cache->get($cache_meta, "photo"))
		{
			$minus = array();
			$one_cat_id = count($cat_ids) == 1 && substr($cat_ids[0], 0, 1) !== '-' ? $cat_ids[0] : false;
			if(! $this->validate_attribute_site_cat('photo', $site_ids, $cat_ids, $minus))
			{
				return false;
			}
			$inner = "";
			$where = '';
			if($cat_ids)
			{
				$inner = " INNER JOIN {photo_category_rel} as r ON r.element_id=e.id"
				." AND r.cat_id IN (".implode(',', $cat_ids).")";
			}
			elseif(! empty($minus["cat_ids"]))
			{
				$inner = " INNER JOIN {photo_category_rel} as r ON r.element_id=e.id"
				." AND r.cat_id NOT IN (".implode(',', $minus["cat_ids"]).")";
			}
			if($site_ids)
			{
				$where .= " AND e.site_id IN (".implode(",", $site_ids).")";
			}
			elseif(! empty($minus["site_ids"]))
			{
				$where .= " AND e.site_id NOT IN (".implode(",", $minus["site_ids"]).")";
			}
			if($tag)
			{
				$t = DB::query_fetch_array("SELECT id, [name] FROM {tags_name} WHERE [name]='%s' AND trash='0'", $tag);
				if(! $tag)
				{
					return false;
				}
				$inner .= " INNER JOIN {tags} AS t ON t.element_id=e.id AND t.element_type='element' AND t.module_name='photo' AND t.tags_name_id=".$t["id"];
			}

			if ($sort == "rand")
			{
				$max_count = DB::query_result(
				"SELECT COUNT(DISTINCT e.id) FROM {photo_category} as e"
				.$inner
				.($this->diafan->configmodules('where_access_element', 'photo') ? " LEFT JOIN {access} AS a ON a.element_id=e.id AND a.module_name='photo' AND a.element_type='element'" : "")
				." WHERE e.[act]='1' AND e.trash='0'"
				.$where
				." AND e.date_start<=%d AND (e.date_finish=0 OR e.date_finish>=%d)"
				.($this->diafan->_site->module == 'photo' && $this->diafan->_route->show ? " AND e.id<>".$this->diafan->_route->show : '')
				.($this->diafan->configmodules('where_access_element', 'photo') ? " AND (e.access='0' OR e.access='1' AND a.role_id=".$this->diafan->_users->role_id.")" : ''), $time, $time
				);
				$rands = array();
				for ($i = 1; $i <= min($max_count, $count); $i++)
				{
					do
					{
						$rand = mt_rand(0, $max_count - 1);
					}
					while (in_array($rand, $rands));
					$rands[] = $rand;
				}
			}
			else
			{
				$rands[0] = 1;
			}
			$result["rows"] = array();

			switch($sort)
			{
				case 'date':
					$order = ' ORDER BY e.id DESC';
					break;

				case 'rand':
					$order = '';
					break;

				default:
					$order = ' ORDER BY e.sort DESC';
			}

			foreach ($rands as $rand)
			{
				$rows = DB::query_range_fetch_all(
				"SELECT e.id, e.[name],e.[anons], e.timeedit, e.site_id,"
				." (SELECT p.id FROM {photo} AS p WHERE p.cat_id = e.id LIMIT 1) AS p_id"
				." FROM {photo_category} AS e"
				.$inner
				.($this->diafan->configmodules('where_access_element', 'photo') ? " LEFT JOIN {access} AS a ON a.element_id=e.id AND a.module_name='photo' AND a.element_type='element'" : "")
				." WHERE e.[act]='1' AND e.trash='0'"
				.($this->diafan->_site->module == 'photo' && $this->diafan->_route->show ? " AND e.id<>".$this->diafan->_route->show : '')
				.$where
				//." AND e.date_start<=%d AND (e.date_finish=0 OR e.date_finish>=%d)"
				.($this->diafan->configmodules('where_access_element', 'photo') ? " AND (e.access='0' OR e.access='1' AND a.role_id=".$this->diafan->_users->role_id.")" : '')
				." GROUP BY e.id"
				.$order,
				$time, $time,
				$sort == "rand" ? $rand : 0,
				$sort == "rand" ? 1     : $count
				);
				$result["rows"] = array_merge($result["rows"], $rows);
			}

			$this->cat_elements($result["rows"], $images_variation);
			if(!empty($result["rows"]) && $tag)
			{
				$result["name"] .= ': '.$t["name"];
			}
			//сохранение кеша
			if ($sort != "rand")
			{
				$this->diafan->_cache->save($result, $cache_meta, "photo");
			}
		}
		foreach ($result["rows"] as &$row)
		{
			$this->prepare_data_element($row);
		}
		foreach ($result["rows"] as &$row)
		{
			$this->format_data_element($row);
		}
		return $result;
	}


	/**
	 * Форматирует данные об альбоме для блока с альбомами
	 *
	 * @param array $rows все полученные из базы данных элементы
	 * @param string $images_variation размер изображений
	 * @return void
	 */
	public function cat_elements(&$rows, $images_variation = 'medium')
	{
		if (empty($this->result["timeedit"]))
		{
			$this->result["timeedit"] = '';
		}
		foreach ($rows as &$row)
		{
			if ($this->diafan->configmodules('page_show', 'photo', $row["site_id"]))
			{
				$this->diafan->_route->prepare($row["site_id"], $row["id"], "photo");
			}
			$this->diafan->_images->prepare($row["id"], "photo");
		}
		foreach ($rows as &$row)
		{
			if (! $this->diafan->configmodules("cat", "photo", $row["site_id"]))
			{
				$row["cat_id"] = 0;
			}
			if ($row["timeedit"] < $this->result["timeedit"])
			{
				$this->result["timeedit"] = $row["timeedit"];
			}

			$row["link"] = $this->diafan->_route->link($row["site_id"], $row["id"], "photo", "cat");

			$images  = $this->diafan->_images->get(
					$images_variation, $row["p_id"], 'photo', 'element',
					$row["site_id"], $row["name"], 0,
					1,
					$row["link"]
				);

			unset($row["cat_id"]);

			$row["img"] = $images ? $images[0] : '';

		}
	}
}
