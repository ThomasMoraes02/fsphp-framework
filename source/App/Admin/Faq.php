<?php 
namespace Source\App\Admin;

use Source\Support\Pager;
use Source\Models\Faq\Channel;
use Source\Models\Faq\Question;

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
        // Create
        if(!empty($data['action']) && $data['action'] == "create") {
            $data = filter_var_array($data, FILTER_SANITIZE_STRING);

            $questionCreate = new Question;
            $questionCreate->channel_id = $data['channel_id'];
            $questionCreate->question = $data['question'];
            $questionCreate->response = $data['response'];
            $questionCreate->order_by = $data['order_by'];

            if($questionCreate->save()) {
                $json['message'] = $questionCreate->message()->render();
                echo json_encode($json);
                return;
            }

            $this->message->success("Pergunta cadastrada com sucesso...")->flash();
            echo json_encode([
                "redirect" => url("/admin/faq/channel/{$questionCreate->channel_id}/{$questionCreate->question_id}"),
            ]);
            return;
        }
        
        // Update
        if(!empty($data['action']) && $data['action'] == "update") {
            $data = filter_var_array($data, FILTER_SANITIZE_STRING);
            $questionId = filter_var($data['question_id'], FILTER_VALIDATE_INT);

            $questionEdit = (new Question)->findById($questionId);
            $questionEdit->channel_id = $data['channel_id'];
            $questionEdit->question = $data['question'];
            $questionEdit->response = $data['response'];
            $questionEdit->order_by = $data['order_by'];

            if($questionEdit->save()) {
                $json['message'] = $questionEdit->message()->render();
                echo json_encode($json);
                return;
            }

            $this->message->success("Pergunta atualizada com sucesso...")->flash();
            echo json_encode([
                "redirect" => url("/admin/faq/channel/{$questionEdit->channel_id}/{$questionEdit->question_id}"),
            ]);

            return;
        }
        
        // Delete
        if(!empty($data['action']) && $data['action'] == "delete") {
            $data = filter_var_array($data, FILTER_SANITIZE_STRING);
            $questionId = filter_var($data['question_id'], FILTER_VALIDATE_INT);

            $questionDelete = (new Question)->findById($questionId);
            $questionDelete->destroy();

            $this->message->success("Canal excluído com sucesso...")->flash();
            echo json_encode([
                "redirect" => url("/admin/faq/home")
            ]);
            return;
        }

        $channel = (new Channel)->findById(filter_var($data['channel_id'], FILTER_VALIDATE_INT));
        $question = null;

        if(!$channel) {
            $this->message->warning("Você tentou gerenciar perguntas um canal que não existe")->flash();
            echo json_encode(["redirect" => url("/admin/faq/home")]);
            return;
        }

        if(!empty($data['question_id'])) {
            $question = (new Question)->findById(filter_var($data['question_id'], FILTER_VALIDATE_INT));
        }

        $head = $this->seo->render(
            CONF_SITE_NAME . " | Faq: Perguntad em {$channel->channel}",
            CONF_SITE_DESC,
            url("/admin"),
            theme("/assets/images/image.jpg", CONF_VIEW_ADMIN),
            false
        );

        echo $this->view->render("widgets/faqs/question", [
            "app" => "faq/home",
            "head" => $head,
            "channel" => $channel,
            "question" => $question
        ]);
    }
}