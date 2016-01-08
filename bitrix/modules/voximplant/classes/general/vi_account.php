<?
class CVoxImplantAccount
{
	private $account_name = null;
	private $account_balance = 0;
	private $account_currency = null;
	private $account_beta_access = false;
	private $error = null;


	function __construct()
	{
		$this->error = new CVoxImplantError(null, '', '');
	}

	public function UpdateAccountInfo()
	{
		$ViHttp = new CVoxImplantHttp();
		$result = $ViHttp->GetAccountInfo();
		if ($result)
		{
			$this->SetAccountName($result->account_name);
			$this->SetAccountBalance($result->account_balance);
			$this->SetAccountCurrency($result->account_currency);
			$this->SetAccountBetaAccess($result->account_beta_access);
		}
		else if ($ViHttp->GetError()->error)
		{
			$this->error = new CVoxImplantError(__METHOD__, $ViHttp->GetError()->code, $ViHttp->GetError()->msg);
			return false;
		}
		return true;
	}

	public function ClearAccountInfo()
	{
		$this->SetAccountName(null);
		$this->SetAccountBalance(0);
		$this->SetAccountCurrency(null);
	}

	public function SetAccountName($name)
	{
		if ($this->account_name == $name)
			return true;

		$this->account_name = $name;

		COption::SetOptionString("voximplant", "account_name", $this->account_name);

		return true;
	}

	public function GetAccountName()
	{
		if (strlen($this->account_name)<=0)
		{
			$this->account_name = COption::GetOptionString("voximplant", "account_name");
			if (strlen($this->account_name)<=0)
			{
				if (!$this->UpdateAccountInfo())
				{
					return false;
				}
			}
		}
		return str_replace('voximplant.com', 'bitrixphone.com', $this->account_name);
	}

	public function GetCallServer()
	{
		return 'ip.'.$this->GetAccountName();
	}

	public function SetAccountBalance($balance)
	{
		$this->account_balance = floatval($balance);

		COption::SetOptionString("voximplant", "account_balance", $this->account_balance);

		return true;
	}

	public function GetAccountBalance($liveBalance = false)
	{
		if ($liveBalance)
			$this->UpdateAccountInfo();

		if (floatval($this->account_balance)<=0)
		{
			$this->account_balance = COption::GetOptionString("voximplant", "account_balance", 0);
			if (floatval($this->account_balance)<=0)
			{
				if (!$this->UpdateAccountInfo())
				{
					return false;
				}
			}
		}
		return floatval($this->account_balance);
	}

	public function SetAccountCurrency($currency)
	{
		if ($this->account_currency == $currency)
			return true;

		$this->account_currency = $currency;

		COption::SetOptionString("voximplant", "account_currency", $this->account_currency);

		return true;
	}

	public function GetAccountCurrency()
	{
		if (strlen($this->account_currency)<=0)
		{
			$this->account_currency = COption::GetOptionString("voximplant", "account_currency");
			if (strlen($this->account_currency)<=0)
			{
				if (!$this->UpdateAccountInfo())
				{
					return false;
				}
			}
		}
		return $this->account_currency;
	}

	public function SetAccountBetaAccess($active = false)
	{
		$active = $active? true: false;

		$this->account_beta_access = $active;

		COption::SetOptionString("voximplant", "account_beta_access", $this->account_beta_access);

		return true;
	}

	public function GetAccountBetaAccess()
	{
		$value = COption::GetOptionString("voximplant", "account_beta_access", $this->account_beta_access);
		return $value? true: false;
	}

	public static function SetPayedFlag($flag)
	{
		COption::SetOptionString("voximplant", "account_payed", $flag == 'Y'? 'Y':'N');

		return true;
	}

	public static function GetPayedFlag()
	{
		return COption::GetOptionString("voximplant", "account_payed");
	}

	public static function SynchronizeInfo()
	{
		return false;
	}

	public function GetError()
	{
		return $this->error;
	}
}