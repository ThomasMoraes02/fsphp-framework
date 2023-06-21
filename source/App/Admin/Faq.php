<?php 
namespace Source\App\Admin;

use Source\Support\Pager;
use Source\Models\Faq\Channel;

class Faq extends Admin
{
    public function __construct()
    {
        parent::__construct();
    }

    public function home(?array $data): void
    {
        $channels = (new Channel)->find();
        $pager = (new Pager(url("/admin/faq/home/")));
        $pager->pager($channels->count(), 5, (!empty($data['page']) ? $data['page'] : 1));

        $head = $this->seo->render(
            CONF_SITE_NAME . " | Faq",
            CONF_SITE_DESC,
            url("/admin"),
            theme("/assets/images/image.jpg", CONF_VIEW_ADMIN),
            false
        );

        echo $this->view->render("widgets/faqs/home", [
            "app" => "faq/home",
            "head" => $head,
            "channels" => $channels->order('channel')->limit($pager->limit())->offset($pager->offset())->fetch(true),
            "paginator" => $pager->render(),
        ]);
    }

    public function channel(?array $data): void
    {
        $head = $this->seo->render(
            CONF_SITE_NAME . " | Faq: Novo Canal",
            CONF_SITE_DESC,
            url("/admin"),
            theme("/assets/images/image.jpg", CONF_VIEW_ADMIN),
            false
        );

        echo $this->view->render("widgets/faqs/channel", [
            "app" => "faq/home",
            "head" => $head,
            "channel" => ""
        ]);
    }

    public function question(?array $data): void
    {
        $head = $this->seo->render(
            CONF_SITE_NAME . " | Faq: Nova Pergunta",
            CONF_SITE_DESC,
            url("/admin"),
            theme("/assets/images/image.jpg", CONF_VIEW_ADMIN),
            false
        );

        echo $this->view->render("widgets/faqs/question", [
            "app" => "faq/home",
            "head" => $head,
            "channel" => (object) [
                "id" => 10
            ],
            "question" => ""
        ]);
    }
}