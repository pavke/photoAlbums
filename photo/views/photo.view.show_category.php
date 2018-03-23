<?php
/**
 * Шаблон блока альбомов
 *
 * Шаблонный тег <insert name="show_category" module="photo" [count="количество"]
 * [cat_id="категория"] [site_id="страница_с_прикрепленным_модулем"]
 * [sort="порядок_вывода"]
 * [images_variation="тег_размера_изображений"]
 * [only_module="only_on_module_page"] [template="шаблон"]>:
 * блок фотографий
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

if (empty($result["rows"]))
{
	return false;
}

echo '<div class="block photo_cat">';

заголовок блока
if (! empty($result["name"]))
{
	echo '<div class="block_header">'.$result["name"].'</div>';
}

//фотографии
foreach ($result["rows"] as $row)
{
	echo '<div class="photo_cat__item">';
	//изобаражение
	if (! empty($row["img"]))
	{
		switch($row["img"]["type"])
		{
			case 'animation':
				echo '<a href="'.BASE_PATH.$row["img"]["link"].'" rel="prettyPhoto[galleryphotoblock]">';
				break;
			case 'large_image':
				echo '<a href="'.BASE_PATH.$row["img"]["link"].'" rel="large_image" width="'.$row["img"]["link_width"].'" height="'.$row["img"]["link_height"].'">';
				break;
			default:
				echo '<a href="'.BASE_PATH_HREF.$row["img"]["link"].'">';
				break;
		}
		echo '<img src="'.$row["img"]["src"].'" width="'.$row["img"]["width"].'" height="'.$row["img"]["height"]
		.'" alt="'.$row["img"]["alt"].'" title="'.$row["img"]["title"].'" class="photo_cat__image">'
		.'</a>';
	}

	//название и ссылка фотографии
	if ($row["name"])
	{
		echo '<div class="photo_cat__name">';
		if ($row["link"])
		{
			echo '<a href="'.BASE_PATH_HREF.$row["link"].'" class="photo_cat__link">';
		}
		echo $row["name"];
		if ($row["link"])
		{
			echo '</a>';
		}
		echo '</div>';
	}

	if(!empty($row["anons"])) {
		echo '<div class="photo_cat__anons">'.$row["anons"].'</div>';
	}


	//вывод рейтинга фотографии
	if (! empty($row["rating"]))
	{
		echo '<div class="rate">' . $row["rating"] . '</div>';
	}
	echo '</div>';
}

echo '</div>';
