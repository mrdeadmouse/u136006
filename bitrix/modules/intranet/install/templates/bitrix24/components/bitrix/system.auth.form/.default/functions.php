<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

class CIntranetPopupShow
{
	private static $instance;
	private $isIntranetPopupShowed = 'N';

	public function init($isIntranetPopupShowed)
	{
		$this->isIntranetPopupShowed = ($isIntranetPopupShowed == "Y" ? "Y" : "N");
	}

	public function isPopupShowed()
	{
		return $this->isIntranetPopupShowed == "N" ? "N" : "Y";
	}

	private function __clone()
	{
	}

	private function __construct()
	{
	}

	public static function getInstance()
	{
		if (is_null(self::$instance))
		{
			self::$instance = new CIntranetPopupShow();
		}

		return self::$instance;
	}
}

?>