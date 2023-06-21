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
        // Create
        if(!empty($data['action']) && $data['action'] == "create") {
            $data = filter_var_array($data, FILTER_SANITIZE_STRING);

            $channelCreate = new Channel;
            $channelCreate->channel = $data['channel'];
            $channelCreate->description = $data['description'];

            if($channelCreate->save()) {
                $json['message'] = $channelCreate->message()->render();
                echo json_encode($json);
                return;
            }

            $this->message->success("Canal cadastrado com sucesso...")->flash();
            echo json_encode([
                "redirect" => url("/admin/faq/channel/{$channelCreate->id}"),
            ]);
            return;
        }

        // Update
        if(!empty($data['action']) && $data['action'] == "update") {
            $data = filter_var_array($data, FILTER_SANITIZE_STRING);
            $channelId = filter_var($data['channel_id'], FILTER_VALIDATE_INT);

            $channelUpdate = (new Channel)->findById($channelId);
            $channelUpdate->channel = $data['channel'];
            $channelUpdate->description = $data['description'];

            if($channelUpdate->save()) {
                $json['message'] = $channelUpdate->message()->render();
                echo json_encode($json);
                return;
            }

            $this->message->success("Canal atualizado com sucesso...")->flash();
            echo json_encode([
                "reload" => true
            ]);

            return;
        }

        // Delete
        if(!empty($data['action']) && $data['action'] == "delete") {
            $data = filter_var_array($data, FILTER_SANITIZE_STRING);
            $channelId = filter_var($data['channel_id'], FILTER_VALIDATE_INT);

            $channelDelete = (new Channel)->findById($channelId);
            $channelDelete->destroy();

            $this->message->success("Canal excluído com sucesso...")->flash();
            echo json_encode([
                "redirect" => url("/admin/faq/home")
            ]);

            return;
        }

        $channelEdit = null;
        if(!empty($data['channel_id'])) {
            $channelId = filter_var($data['channel_id'], FILTER_VALIDATE_INT);
            $channelEdit = (new Channel)->findById($channelId);
        }

        $head = $this->seo->render(
            CONF_SITE_NAME . " | " . ($channelEdit ? "FAQ: {$channelEdit->channel}" : "FAQ: Novo Canal"),
            CONF_SITE_DESC,
            url("/admin"),
            theme("/assets/images/image.jpg", CONF_VIEW_ADMIN),
            false
        );

        echo $this->view->render("widgets/faqs/channel", [
            "app" => "faq/home",
            "head" => $head,
            "channel" => $channelEdit
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