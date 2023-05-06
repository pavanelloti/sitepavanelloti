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

    public static function user(): ?User
    {
        $session = new Session();
        if(!$session->has("authUser")){
            return null;
        }

        return (new User())->findById($session->authUser);
    }

    public static function logout(): void
    {
        $session = new Session();
        $session->unset("authUser");
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

    public function forget(string $email): bool
    {
        if (!is_email($email)) {
            $this->message->warning("Email informado não é válido");
            return false;
        }

        $user = (new User())->findByEmail($email);

        if (!$user) {
            $this->message->warning("E-Mail Informando não está cadastrado");
            return false;
        }

        $user->forget = md5(uniqid(rand(), true));
        $user->save();

        $view = new View(__DIR__ . "/../../shared/views/email");
        $message = $view->render("forget", [
            "first_name"=>$user->first_name,
            "forget_link"=> url("/recuperar/{$user->email}|{$user->forget}")
        ]);

        (new Email())->bootstrap(
            "Recupere sua senha no " . CONF_SITE_NAME,
            $message,
            $user->email,
            "{$user->first_name} {$user->last_name}" 
        )->send();
        
        return true;
    }

    public function reset(string $email, string $code, string $password, string $passwordRe): bool
    {
        $user = (new User())->findByEmail($email);

        if (!$user) {
            $this->message->warning("A conta para recuperação não foi encontrada");
            return false;
        }

        if ($user->forget != $code) {
            $this->message->error("Desculpe, mas código de verificação não é mais válido");
            return false;
        }

        if (!is_passwd($password)) {
            $min = CONF_PASSWD_MIN_LEN;
            $max = CONF_PASSWD_MAX_LEN;
            $this->message->info("Sua senha deve ter entre {$min} e {$max} Caracteres");
            return false;
        }
        
        if ($password !== $passwordRe){
            $this->message->warning("Senhas digitadas não confere");
            return false;
        }

        $user->password = $password;
        $user->forget = null;
        $user->save();
        return true;

    }

}