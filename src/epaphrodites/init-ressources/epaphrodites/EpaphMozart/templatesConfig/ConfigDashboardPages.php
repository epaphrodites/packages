<?php

namespace Epaphrodites\epaphrodites\EpaphMozart\templatesConfig;

class ConfigDashboardPages extends ConfigUsersMainPages
{

    private $interface;

    /**
     * Admin interface manager
     * 
     * @param string $key|null
     * @return string
     */
    public function admin(
        int|null $key = null, 
        string|null $url = null
    ):string{

        if ($key !== null) {

            $this->interface =
                [
                    1 => "{$url}super_admin/",
                    2 => "{$url}administrator/",
                    3 => "{$url}users/",
                ];

            return $this->interface[$key];
        } else {
            return $this->login() . $url;
        }
    }

    /** 
     * Login interface manager
     */
    public function identification():string
    {

        $this->interface = 'users/edit_users_infos/';

        return $this->interface;
    } 
    
    /** 
     * OTP verification interface manager
     */
    public function otpVerification():string
    {

        $this->interface = _FAKE_."confirm_otp_code_send_by_email/";

        return $this->interface;
    }  

}
