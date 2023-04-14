<?php 
namespace Source\App;

use Source\Models\Auth;
use Source\Core\Controller;
use Source\Support\Message;
use Source\Models\Report\Access;
use Source\Models\Report\Online;
use Source\Models\User;

class App extends Controller
{
    private User $user;

    public function __construct()
    {
        parent::__construct(__DIR__ . "/../../themes/" . CONF_VIEW_APP . "/");

        $this->user = Auth::user();
        if(!$this->user == Auth::user()) {
            $this->message->warning("Efetue login para acessar o APP")->flash();
            redirect("/entrar");
        }

        (new Access)->report();
        (new Online)->report();
    }

    public function home(): void
    {
        echo $this->view->render("home", []);
    }

    public function logout(): void
    {
        (new Message)->info("Você saiu com sucesso " . Auth::user()->first_name . " Volte logo :)")->flash();

        Auth::logout();
        redirect("/entrar");
    }
}