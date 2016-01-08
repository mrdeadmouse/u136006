<?php

namespace Bitrix\Disk\Security;


use Bitrix\Main\Security\Sign\Signer;

class ParameterSigner
{
	public static function getImageSignature($id, $width, $height)
	{
		$sign = new Signer;
		return $sign->getSignature($id . '|' . (int)$width . 'x' . (int)$height , 'disk.image.size');
	}

	public static function validateImageSignature($signature, $id, $width, $height)
	{
		$sign = new Signer;
		return $sign->validate($id . '|' . (int)$width . 'x' . (int)$height, $signature, 'disk.image.size');
	}
}