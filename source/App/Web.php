<?php 
namespace Source\App;

use Source\Core\Controller;
use stdClass;

class Web extends Controller
{
    public function __construct()
    {
        parent::__construct(__DIR__ . "/../../themes/".CONF_VIEW_THEME."/");
    }

    public function home(): void
    {
        $head = $this->seo->render(
            CONF_SITE_NAME . " - " . CONF_SITE_TITLE,
            CONF_SITE_DESC,
            url(),
            url("/assets/images/share.jpg"),
        );

        echo $this->view->render("home", [
            "head" => $head,
            "video" => "sNBLOxxDPrc"
        ]);
    }

    public function about()
    {
        $head = $this->seo->render(
            "Descubra o " . CONF_SITE_NAME . " - " . CONF_SITE_DESC,
            CONF_SITE_DESC,
            url("/sobre"),
            url("/assets/images/share.jpg"),
        );

        echo $this->view->render("about", [
            "head" => $head,
            "video" => "sNBLOxxDPrc"
        ]);
    }

    public function terms()
    {
        $head = $this->seo->render(
            CONF_SITE_NAME . " - Termos de Uso",
            CONF_SITE_DESC,
            url("/terms"),
            url("/assets/images/share.jpg"),
        );

        echo $this->view->render("terms", [
            "head" => $head
        ]);
    }

    public function error(array $data): void
    {
        $error = new stdClass;
        $error->code = $data['errcode'];
        $error->title = "Ooops. Conteúdo indisponível :/";
        $error->message = "Sentimos muito, mas o conteúdo que você tentou acessar não existe, está indposnível no momento ou foi removido :/";
        $error->linkTitle = "Continue navegando!";
        $error->link = url_back();

        $head = $this->seo->render(
            "{$error->code} | {$error->title}",
            $error->message,
            url("/ops/{$error->code}"),
            url("/assets/images/share.jpg"),
            false
        );

        echo $this->view->render("error", [
            "head" => $head,
            "error" => $error
        ]);
    }
}