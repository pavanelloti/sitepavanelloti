<?php

namespace Source\Models;

use Source\Core\View;
use Source\Core\Model;
use Source\Models\User;
use Source\Core\Session;
use Source\Support\Email;

class Auth extends Model
{ 

    public function __construct()
    {
        parent::__construct("users", ["id"], ["email", "password"]);
    }

    public function register(User $user): bool
    {

        if(!$user->save()){
            $this->message = $user->message;
            return false;
        }

        $view = new View(__DIR__ . "/../../shared/views/email");
        $message = $view->render("confirm", [
            "first_name"=>$user->first_name,
            "confirm_link"=> url("/obrigado/". base64_encode($user->email))
        ]);

        (new Email())->bootstrap(
            "Ative sua conta no ". CONF_SITE_NAME,
            $message,
            $user->email,
            "{$user->first_name} {$user->last_name}"
        )->send();
        
        return true;
    }

    public function login(string $email, string $password, bool $save = false): bool
    {
        if (!is_email($email)){
            $this->message->warning("Email informado não é válido");
            return false;
        }

        if ($save)
        {
            setcookie("authEmail", $email,  time() + 604800, "/");
        }else{
            setcookie("authEmail", '',  time() - 604800);
        }

        if (!is_passwd($password)){
            $this->message->warning("Senha informada não é válida");
            return false;
        }

        $user = (new User())->findByEmail($email);
        
        if (!$user){
            $this->message->warning("Email ou senha não confere.");
            return false;
        }

        if (!passwd_verify($password, $user->password)) {
            $this->message->warning("Email ou senha não confere!");
            return false;
        }

        if (passwd_rehash($user->password)){
            $user->password = $password;
            $user->save();
        }

        //Login
        (new Session())->set("authUser", $user->id);
        $this->message->success("Login Efetuado com sucesso")->flash();
        return true;
    }

}