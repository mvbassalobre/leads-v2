<?php

use Carbon\Carbon;

if (!function_exists('completeFormatedDate')) {
	function completeFormatedDate($date, $weekday = true, $day = true, $year = true)
	{
		date_default_timezone_set('America/Sao_Paulo');
		setlocale(LC_ALL, 'pt_BR.utf-8', 'ptb', 'pt_BR', 'portuguese-brazil', 'portuguese-brazilian', 'bra', 'brazil', 'br');
		setlocale(LC_TIME, 'pt_BR.utf-8', 'ptb', 'pt_BR', 'portuguese-brazil', 'portuguese-brazilian', 'bra', 'brazil', 'br');
		$format = '';
		if ($weekday) {
			$format .= "%A ";
			if ($day || $year) $format .= " , ";
		}
		if ($day) {
			$format .= "%d de %B ";
			if ($year) $format .= "de ";
		}
		if ($year) $format .= "%Y";
		return  Carbon::create($date)->formatLocalized($format);
	}
}

if (!function_exists('formatCnpjCpf')) {
	function formatCnpjCpf($value)
	{
		$cnpj_cpf = preg_replace("/\D/", '', $value);
		if (strlen($cnpj_cpf) === 11)  return preg_replace("/(\d{3})(\d{3})(\d{3})(\d{2})/", "\$1.\$2.\$3-\$4", $cnpj_cpf);
		return preg_replace("/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/", "\$1.\$2.\$3/\$4-\$5", $cnpj_cpf);
	}
}


if (!function_exists('formatPhoneNumber')) {
	function formatPhoneNumber($TEL)
	{

		$TEL = preg_replace('/[^0-9]/', '', str_replace("+55", "", $TEL));
		$tam = strlen(preg_replace("/[^0-9]/", "", $TEL));
		if ($tam == 11) {
			return "(" . substr($TEL, 0, 2) . ") " . substr($TEL, 2, 5) . "-" . substr($TEL, 7, 11);
		}
		if ($tam == 10) {
			return "(" . substr($TEL, 0, 2) . ") " . substr($TEL, 2, 4) . "-" . substr($TEL, 6, 10);
		}
	}
}

if (!function_exists('removeCpfCnpfMask')) {
	function removeCpfCnpfMask($value)
	{
		$value = preg_replace('/[^0-9]/', '', $value);
		return $value;
	}
}

if (!function_exists('hasPermissionTo')) {
	function hasPermissionTo($permission)
	{
		if (!\Auth::check()) return false;
		$user = \Auth::user();
		if ($user->hasRole(["super-admin", "admin"])) return true;
		$permission = trim($permission);
		return $user->can($permission);
	}
}

if (!function_exists('hasAccessToModule')) {
	function hasAccessToModule($module_ref)
	{
		if (!\Auth::check()) return false;
		$user = \Auth::user();
		if ($user->hasRole(["super-admin"])) return true;
		$module_ref = trim($module_ref);
		return $user->canAccessModule($module_ref);
	}
}

if (!function_exists('mysql_json_like')) {
	function mysql_json_like($column, $value)
	{
		$acents = [
			"a" => ["à", "á", 'ã', 'â'],
			"e" => ["è", "é", "ê"],
			"i" => ["ì", "í"],
			"o" => ["ó", "ô", "õ"],
			"u" => ["ú", "ü"],
		];
		foreach ($acents as $letter => $items) foreach ($items as $item)  $column = "replace($column,'$item','$letter')";
		$value = preg_replace(array("/(á|à|ã|â|ä)/", "/(Á|À|Ã|Â|Ä)/", "/(é|è|ê|ë)/", "/(É|È|Ê|Ë)/", "/(í|ì|î|ï)/", "/(Í|Ì|Î|Ï)/", "/(ó|ò|õ|ô|ö)/", "/(Ó|Ò|Õ|Ô|Ö)/", "/(ú|ù|û|ü)/", "/(Ú|Ù|Û|Ü)/", "/(ñ)/", "/(Ñ)/"), explode(" ", "a A e E i I o O u U n N"), $value);
		return "lower($column) like lower('%$value%')";
	}
}

if (!function_exists('toRawSql')) {
	function toRawSql($query)
	{
		return array_reduce($query->getBindings(), function ($sql, $binding) {
			return preg_replace('/\?/', is_numeric($binding) ? $binding : "'" . $binding . "'", $sql, 1);
		}, $query->toSql());
	}
}

if (!function_exists('toMoney')) {
	function toMoney($value, $prefix = "R$")
	{
		return $prefix . " " . number_format(floatval($value), 2, ',', '.');
	}
}

if (!function_exists('formatId')) {
	function formatId($value, $prefix = "#")
	{
		return $prefix . str_pad($value, 8, "0", STR_PAD_LEFT);
	}
}

if (!function_exists('formatDate')) {
	function formatDate($date, $format = "d/m/Y - H:i:s")
	{
		if (!$date) {
			return null;
		}
		return @$date->format($format);
	}
}

if (!function_exists('queryBetweenDates')) {
	function queryBetweenDates($column, $dates)
	{
		return "DATE($column) >= DATE('$dates[0]') and DATE($column) <= DATE('$dates[1]')";
	}
}

if (!function_exists('setModelDataValue')) {
	function setModelDataValue($self, $field, $value)
	{
		$self->data = (object)array_merge(@$self->data ? (array) $self->data : [], [$field => $value]);
	}
}

if (!function_exists('getEnabledIcon')) {
	function getEnabledIcon($enabled = false)
	{
		$icons = [
			true => '🟢',
			false => '🔴',
		];
		return @$icons[$enabled] ?? '🟡';
	}
}

if (!function_exists('snakeCaseToCamelCase')) {
	function snakeCaseToCamelCase($string, $capitalizeFirstCharacter = false)
	{
		$str = str_replace('-', '', ucwords($string, '-'));
		if (!$capitalizeFirstCharacter) {
			$str = lcfirst($str);
		}
		return $str;
	}
}

if (!function_exists('Obj2Array')) {
	function Obj2Array($oject)
	{
		$array = json_decode(json_encode($oject), true);
		return $array;
	}
}

if (!function_exists('stripAccents')) {
	function stripAccents($value)
	{
		return preg_replace(array("/(á|à|ã|â|ä)/", "/(Á|À|Ã|Â|Ä)/", "/(é|è|ê|ë)/", "/(É|È|Ê|Ë)/", "/(í|ì|î|ï)/", "/(Í|Ì|Î|Ï)/", "/(ó|ò|õ|ô|ö)/", "/(Ó|Ò|Õ|Ô|Ö)/", "/(ú|ù|û|ü)/", "/(Ú|Ù|Û|Ü)/", "/(ñ)/", "/(Ñ)/"), explode(" ", "a A e E i I o O u U n N"), $value);
	}
}
