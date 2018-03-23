<?php
/**
 * Контроллер
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
 * Photo
 */
class Photo extends Controller
{
	/**
	 * Шаблонная функция: выводит несколько альбомов.
	 *
	 * @param array $attributes атрибуты шаблонного тега
	 * count - количество выводимых альбомов (по умолчанию 3)
	 * site_id - страницы, к которым прикреплен модуль. Идентификаторы страниц перечисляются через запятую. Можно указать отрицательное значение, тогда будут исключены фотографии из указанного раздела. По умолчанию выбираются все страницы
	 * cat_id - альбомы фотографий, если в настройках модуля отмечено «Использовать альбомы». Идентификаторы альбомов перечисляются через запятую. Можно указать отрицательное значение, тогда будут исключены фотографии из указанной категории. Можно указать значение **current**, тогда будут показаны фотографии из текущей (открытой) категории или из всех категорий, если ни одна категория не открыта. По умолчанию альбом не учитывается, выводятся все фотографии
	 * sort - сортировка фотографий: по умолчанию как на странице модуля, **date** – по дате, **rand** – в случайном порядке
	 * images_variation - тег размера изображений, задается в настроках модуля
	 * only_module - выводить блок только на странице, к которой прикреплен модуль «Фотогалерея»: **true** – выводить блок только на странице модуля, по умолчанию блок будет выводиться на всех страницах
	 * tag - тег, прикрепленный к фотографиям
	 * defer - маркер отложенной загрузки шаблонного тега: **event** – загрузка контента только по желанию пользователя при нажатии кнопки "Загрузить", **emergence** – загрузка контента только при появлении в окне браузера клиента, **async** – асинхронная (одновременная) загрузка контента совместно с контентом шаблонных тегов с тем же маркером, **sync** – синхронная (последовательная) загрузка контента совместно с контентом шаблонных тегов с тем же маркером, по умолчанию загрузка контента только по желанию пользователя
	 * defer_title - текстовая строка, выводимая на месте появления загружаемого контента с помощью отложенной загрузки шаблонного тега
	 * template - шаблон тега (файл modules/photo/views/photo.view.show_block_**template**.php; по умолчанию шаблон modules/photo/views/photo.view.show_block.php)
     * @param string $image_album выводить фото альбома или последнюю фотографию
	 * @return void
	 */
	new public function show_category($attributes)
	{
		$attributes = $this->get_attributes($attributes, 'count', 'site_id', 'cat_id', 'sort', 'images_variation', 'only_module', 'tag', 'template', 'image_album');

		$count   = $attributes["count"] ? intval($attributes["count"]) : 3;
		$site_ids = explode(",", $attributes["site_id"]);
		$cat_ids  = explode(",", $attributes["cat_id"]);
		$sort    = $attributes["sort"] == "date" || $attributes["sort"] == "rand" ? $attributes["sort"] : "";
		$images_variation = $attributes["images_variation"] ? strval($attributes["images_variation"]) : 'medium';
		$tag = $attributes["tag"] && $this->diafan->configmodules('tags', 'photo') ? strval($attributes["tag"]) : '';
        $image_album = (bool) $attributes["image_album"];

		if ($attributes["only_module"] && ($this->diafan->_site->module != "photo" || in_array($this->diafan->_site->id, $site_ids)))
			return;

		if($attributes["cat_id"] == "current")
		{
			if($this->diafan->_site->module == "photo" && (empty($site_ids[0]) || in_array($this->diafan->_site->id, $site_ids))
			   && $this->diafan->_route->cat)
			{
				$cat_ids[0] = $this->diafan->_route->cat;
			}
			else
			{
				$cat_ids = array();
			}
		}

		$result = $this->model->show_category($count, $site_ids, $cat_ids, $sort, $images_variation, $tag, $image_album);
		$result["attributes"] = $attributes;

		echo $this->diafan->_tpl->get('show_category', 'photo', $result, $attributes["template"]);
	}
}
