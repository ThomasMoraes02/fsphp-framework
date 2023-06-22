<?php 
namespace Source\App\Admin;

use Source\Models\User;
use Source\Support\Pager;
use Source\App\Admin\Admin;
use Source\Support\Thumb;
use Source\Support\Upload;

class Users extends Admin
{
    public function __construct()
    {
        parent::__construct();
    }

    public function home(?array $data): void
    {
        if(!empty($data['s'])) {
            $s = filter_var($data['s'], FILTER_SANITIZE_STRING);
            echo json_encode(["redirect" => url("/admin/users/home/{$s}/1")]);
            return;
        }

        $search = null;
        $users = (new User)->find();

        if(!empty($data['search']) && $data['search'] != "all") {
            $search = filter_var($data['search'], FILTER_SANITIZE_STRING);
            $users = (new User)->find("MATCH(first_name, last_name, email) AGAINST(:search)", "search={$search}");
        }

        $all = ($search ?? "all");
        $pager = (new Pager(url("/admin/users/home/{$all}/")));
        $pager->pager($users->count(), 5, (!empty($data['page']) ? $data['page'] : 1));

        $head = $this->seo->render(
            CONF_SITE_NAME . " | Usuários",
            CONF_SITE_DESC,
            url("/admin"),
            theme("/assets/images/image.jpg", CONF_VIEW_ADMIN),
            false
        );

        echo $this->view->render("widgets/users/home", [
            "app" => "users/home",
            "head" => $head,
            "search" => $search,
            "users" => $users->order("first_name, last_name")->limit($pager->limit())->offset($pager->offset())->fetch(true),
            "paginator" => $pager->render(),
        ]);
    }

    public function user(?array $data): void
    {
        // Create
        if(!empty($data['action']) && $data['action'] == "create") {
            $data = filter_var_array($data, FILTER_SANITIZE_STRING);

            $userCreate = new User;
            $userCreate->first_name = $data['first_name'];
            $userCreate->last_name = $data['last_name'];
            $userCreate->email = $data['email'];
            $userCreate->password = $data['password'];
            $userCreate->level = $data['level'];
            $userCreate->genre = $data['genre'];
            $userCreate->datebirth = date_fmt_back($data['datebirth']);
            $userCreate->document = preg_replace("/[^0-9]/", "", $data['document']);
            $userCreate->status = $data['status'];

            if(!empty($_FILES['photo'])) {
                $files = $_FILES['photo'];
                $upload = new Upload;
                $image = $upload->image($files, $userCreate->full_name(), 600);

                if(!$image) {
                    $json['message'] = $upload->message()->render();
                    echo json_encode($json);
                    return;
                }

                $userCreate->photo = $image;
            }

            if(!$userCreate->save()) {
                $json['message'] = $userCreate->message()->render();
                echo json_encode($json);
                return;
            }

            $this->message->success("Usuário cadastrado com sucesso...")->flash();
            echo json_encode(["redirect" => url("/admin/users/user/{$userCreate->id}"),]);
            return;
        }

        // Update
        if(!empty($data['action']) && $data['action'] == "update") {
            $data = filter_var_array($data, FILTER_SANITIZE_STRING);
            $userUpdate = (new User)->findById($data['user_id']);

            if(!$userUpdate) {
                $this->message->error("Você tentou alterar um usuário inexistente")->flash();
                echo json_encode(["redirect" => url("/admin/users/home")]);
                return;
            }

            $userUpdate->first_name = $data['first_name'];
            $userUpdate->last_name = $data['last_name'];
            $userUpdate->email = $data['email'];
            $userUpdate->password = (!empty($data['password']) ? $data['password'] : $userUpdate->password);
            $userUpdate->level = $data['level'];
            $userUpdate->genre = $data['genre'];
            $userUpdate->datebirth = date_fmt_back($data['datebirth']);
            $userUpdate->document = preg_replace("/[^0-9]/", "", $data['document']);
            $userUpdate->status = $data['status'];

            if(!empty($_FILES['photo'])) {
                if($userUpdate->photo && file_exists(__DIR__ . "/../../../". CONF_UPLOAD_DIR . "/{$userUpdate->photo}")) {
                    unlink(__DIR__ . "/../../../". CONF_UPLOAD_DIR . "/{$userUpdate->photo}");
                    (new Thumb)->flush($userUpdate->photo);
                }

                $files = $_FILES['photo'];
                $upload = new Upload;
                $image = $upload->image($files, $userUpdate->full_name(), 600);

                if(!$image) {
                    $json['message'] = $upload->message()->render();
                    echo json_encode($json);
                    return;
                }

                $userUpdate->photo = $image;
            }

            if(!$userUpdate->save()) {
                $json['message'] = $userUpdate->message()->render();
                echo json_encode($json);
                return;
            }

            $this->message->success("Usuário atualizado com sucesso...")->flash();
            echo json_encode(["reload" => true]);
            return;
        }


        // Delete
        if(!empty($data['action']) && $data['action'] == "delete") {
            $userId = filter_var($data['user_id'], FILTER_VALIDATE_INT);

            $userDelete = (new User)->findById($userId);

            if(!$userDelete) {
                $this->message->error("Você tentou deletar um usuário inexistente")->flash();
                echo json_encode(["redirect" => url("/admin/users/home")]);
                return;
            }

            $userDelete->destroy();

            $this->message->success("Usuário deletado com sucesso...")->flash();
            echo json_encode(["redirect" => url("/admin/users/home")]);
            return;
        }

        $userEdit = null;
        if(!empty($data['user_id'])) {
            $userEdit = (new User)->findById($data['user_id']);
        }

        $head = $this->seo->render(
            CONF_SITE_NAME . " | " . ($userEdit ? "Perfil de {$userEdit->full_name()}" : "Novo Usuário"),
            CONF_SITE_DESC,
            url("/admin"),
            theme("/assets/images/image.jpg", CONF_VIEW_ADMIN),
            false
        );

        echo $this->view->render("widgets/users/user", [
            "app" => "users/user",
            "head" => $head,
            "user" => $userEdit,
        ]);
    }
}