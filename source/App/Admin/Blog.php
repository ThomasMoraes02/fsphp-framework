<?php 
namespace Source\App\Admin;

use Source\Models\Category;
use Source\Models\Post;
use Source\Models\User;
use Source\Support\Pager;
use Source\Support\Thumb;
use Source\Support\Upload;

class Blog extends Admin
{
    public function __construct()
    {
        parent::__construct();
    }

    public function home(?array $data): void
    {
        // Search redirect
        if(!empty($data['s'])) {
            $s = filter_var($data['s'], FILTER_SANITIZE_STRING);
            echo json_encode([
                'redirect' => url("/admin/blog/home/{$s}/1")
            ]);
            return;
        }


        $search = null;
        $posts = (new Post)->find();

        if(!empty($data['search']) && $data['search'] != "all") {
            $search = filter_var($data['search'], FILTER_SANITIZE_STRING);
            $posts = (new Post)->find("MATCH(title,subtitle) AGAINST(:s)", "s={$search}");
            if(!$posts->count()) {
                $this->message->info("Sua pesquisa não retornou resultados")->flash();
                redirect(url("/admin/blog/home"));
            }
        }

        $all = ($search ?? "all");
        $pager = new Pager(url("/admin/blog/home/{$all}/"));
        $pager->pager($posts->count(), 12, (!empty($data['page']) ? $data['page'] : 1));

        $head = $this->seo->render(
            CONF_SITE_NAME . " | Blog",
            CONF_SITE_DESC,
            url("/admin"),
            theme("/assets/images/image.jpg", CONF_VIEW_ADMIN),
            false
        );

        echo $this->view->render("widgets/blog/home", [
            "app" => "blog/home",
            "head" => $head,
            "posts" => $posts->limit($pager->limit())->offset($pager->offset())->order("post_at DESC")->fetch(true),
            "paginator" => $pager->render(),
            "search" => $search,
        ]);
    }

    public function post(?array $data): void
    {
        // MCE Upload
        if(!empty($data['upload']) && !empty($_FILES['image'])) {
            $files = $_FILES['image'];
            $upload = new Upload();
            $image = $upload->image($files, "post-".time());

            if(!$image) {
                $json['message'] = $upload->message()->render();
                echo json_encode($json);
                return;
            }

            $json['mce_image'] = '<img style="width: 100%;" src="'.url("/storage/{$image}").'" title="{title}" alt="{title}">';
            echo json_encode($json);
            return;
        }

        // Create
        if(!empty($data['action']) && $data['action'] == "create") {
            $content = $data['content'];
            $data = filter_var_array($data, FILTER_SANITIZE_STRIPPED);

            $postCreate = new Post;
            $postCreate->author = $data['author'];
            $postCreate->category = $data['category'];
            $postCreate->title = $data['title'];
            $postCreate->uri = str_slug($postCreate->title);
            $postCreate->subtitle = $data['subtitle'];
            $postCreate->content = str_replace(["{title}"], [$postCreate->title], $content);
            $postCreate->video = $data['video'];
            $postCreate->status = $data['status'];
            $postCreate->post_at = date('Y-m-d H:i:s', strtotime(str_replace('/', '-', $data['post_at'])));

            if(!empty($_FILES['cover'])) {
                $files = $_FILES['cover'];
                $upload = new Upload();
                $image = $upload->image($files, $postCreate->title);

                if(!$image) {
                    $json['message'] = $upload->message()->render();
                    echo json_encode($json);
                    return;
                }

                $postCreate->cover = $image;
            }

            if(!$postCreate->save()) {
                $json['message'] = $postCreate->message()->render();
                echo json_encode($json);
                return;
            }

            $this->message->success("Post publicado com sucesso...")->flash();
            $json["redirect"] = url("/admin/blog/post/{$postCreate->id}");

            echo json_encode($json);
            return;
        }

        // Update
        if(!empty($data['action']) && $data['action'] == "update") {
            $content = $data['content'];
            $data = filter_var_array($data, FILTER_SANITIZE_STRIPPED);
            $postEdit = (new Post)->findById($data['post_id']);

            if(!$postEdit) {
                $this->message->error("Você tentou atualizar um post que não existe ou foi removido")->flash();
                echo json_encode([
                    "redirect" => url("/admin/blog/home")
                ]);
                return;
            }

            $postEdit->author = $data['author'];
            $postEdit->category = $data['category'];
            $postEdit->title = $data['title'];
            $postEdit->uri = str_slug($postEdit->title);
            $postEdit->subtitle = $data['subtitle'];
            $postEdit->content = str_replace(["{title}"], [$postEdit->title], $content);
            $postEdit->video = $data['video'];
            $postEdit->status = $data['status'];
            $postEdit->post_at = date('Y-m-d H:i:s', strtotime(str_replace('/', '-', $data['post_at'])));

            if(!empty($_FILES['cover'])) {
                if($postEdit->cover && file_exists(__DIR__ . "/../../../".CONF_UPLOAD_DIR."/{$postEdit->cover}")) {
                    unlink(__DIR__ . "/../../../".CONF_UPLOAD_DIR."/{$postEdit->cover}");
                    (new Thumb)->flush($postEdit->cover);
                }

                $files = $_FILES['cover'];
                $upload = new Upload();
                $image = $upload->image($files, $postEdit->title);

                if(!$image) {
                    $json['message'] = $upload->message()->render();
                    echo json_encode($json);
                    return;
                }

                $postEdit->cover = $image;
            }

            if(!$postEdit->save()) {
                $json['message'] = $postEdit->message()->render();
                echo json_encode($json);
                return;
            }

            $this->message->success("Post atualizado com sucesso...")->flash();
            echo json_encode([
                "reload" => true
            ]);
            return;
        }

        // Delete
        if(!empty($data['action']) && $data['action'] == "delete") {
            $data = filter_var_array($data, FILTER_SANITIZE_STRIPPED);
            $postDelete = (new Post)->findById($data['post_id']);

            if(!$postDelete) {
                $this->message->error("Você tentou remover um post que não existe ou foi removido")->flash();
                echo json_encode(["reload" => true]);
                return;
            }

            if($postDelete->cover && file_exists(__DIR__ . "/../../../".CONF_UPLOAD_DIR."/{$postDelete->cover}")) {
                unlink(__DIR__ . "/../../../".CONF_UPLOAD_DIR."/{$postDelete->cover}");
                (new Thumb)->flush($postDelete->cover);
            }

            $postDelete->destroy();
            $this->message->success("Post removido com sucesso...")->flash();
            echo json_encode(["reload" => true]);
            return;
        }

        $postEdit = null;
        if(!empty($data['post_id'])) {
            $postId = filter_var($data['post_id'], FILTER_SANITIZE_NUMBER_INT);
            $postEdit = (new Post)->findById($postId);
        }

        $head = $this->seo->render(
            CONF_SITE_NAME . " | " . ($postEdit->title ?? "Novo Artigo"),
            CONF_SITE_DESC,
            url("/admin"),
            theme("/assets/images/image.jpg", CONF_VIEW_ADMIN),
            false
        );

        echo $this->view->render("widgets/blog/post", [
            "app" => "blog/post",
            "head" => $head,
            "post" => $postEdit,
            "categories" => (new Category)->find("type = :type", "type=post")->order("title")->fetch(true),
            "authors" => (new User)->find("level >= :level", "level=5")->fetch(true),
        ]);
    }

    public function categories(?array $data): void
    {
        $categories = (new Category)->find();
        $pager = new Pager(url("/admin/blog/categories/"));
        $pager->pager($categories->count(), 6, (!empty($data['page']) ? $data['page'] : 1));

        $head = $this->seo->render(
            CONF_SITE_NAME . " | " . "Categorias",
            CONF_SITE_DESC,
            url("/admin"),
            theme("/assets/images/image.jpg", CONF_VIEW_ADMIN),
            false
        );

        echo $this->view->render("widgets/blog/categories", [
            "app" => "blog/categories",
            "head" => $head,
            "categories" => $categories->order("title")->limit($pager->limit())->offset($pager->offset())->fetch(true),
            "paginator" => $pager->render()
        ]);
    }

    public function category(?array $data): void
    {
        // Create
        if(!empty($data['action']) && $data['action'] == "create") {
            $data = filter_var_array($data, FILTER_SANITIZE_STRIPPED);
            $categoryCreate = new Category;
            $categoryCreate->title = $data['title'];
            $categoryCreate->uri = str_slug($categoryCreate->title);
            $categoryCreate->description = $data['description'];

            if(!empty($_FILES['cover'])) {
                $files = $_FILES['cover'];
                $upload = new Upload();
                $image = $upload->image($files, $categoryCreate->title);

                if(!$image) {
                    $json['message'] = $upload->message()->render();
                    echo json_encode($json);
                    return;
                }

                $categoryCreate->cover = $image;
            }

            if(!$categoryCreate->save()) {
                $json['message'] = $categoryCreate->message()->render();
                echo json_encode($json);
                return;
            }

            $this->message->success("Categoria criada com sucesso...")->flash();
            echo json_encode([
                "redirect" => url("/admin/blog/category/{$categoryCreate->id}")
            ]);
            return;
        }

        // Update
        if(!empty($data['action']) && $data['action'] == "update") {
            $data = filter_var_array($data, FILTER_SANITIZE_STRIPPED);
            $categoryEdit = (new Category)->findById($data['category_id']);

            if(!$categoryEdit) {
                $this->message->error("Você tentou atualizar uma categoria que não existe ou foi removida")->flash();
                echo json_encode([
                    "redirect" => url("/admin/blog/home")
                ]);
                return;
            }

            $categoryEdit->title = $data['title'];
            $categoryEdit->uri = str_slug($categoryEdit->title);
            $categoryEdit->description = $data['description'];

            if(!empty($_FILES['cover'])) {
                if($categoryEdit->cover && file_exists(__DIR__ . "/../../../".CONF_UPLOAD_DIR."/{$categoryEdit->cover}")) {
                    unlink(__DIR__ . "/../../../".CONF_UPLOAD_DIR."/{$categoryEdit->cover}");
                    (new Thumb)->flush($categoryEdit->cover);
                }

                $files = $_FILES['cover'];
                $upload = new Upload();
                $image = $upload->image($files, $categoryEdit->title);

                if(!$image) {
                    $json['message'] = $upload->message()->render();
                    echo json_encode($json);
                    return;
                }

                $categoryEdit->cover = $image;
            }

            if(!$categoryEdit->save()) {
                $json['message'] = $categoryEdit->message()->render();
                echo json_encode($json);
                return;
            }

            $this->message->success("Categoria atualizada com sucesso...")->flash();
            echo json_encode([
                "reload" => true
            ]);
            return;
        }

        // Delete
        if(!empty($data['action']) && $data['action'] == "delete") {
            $data = filter_var_array($data, FILTER_SANITIZE_STRIPPED);

            $categoryDelete = (new Category)->findById($data['category_id']);

            if(!$categoryDelete) {
                $this->message->error("Você tentou remover uma categoria que não existe ou foi removida")->flash();
                echo json_encode([
                    "reload" => true
                ]);
                return;
            }

            if($categoryDelete->posts()->count()) {
                $json['message'] = $this->message->warning("Você tentou remover uma categoria que possui posts")->flash();
                echo json_encode($json);
                return;
            }

            if($categoryDelete->cover && file_exists(__DIR__ . "/../../../".CONF_UPLOAD_DIR."/{$categoryDelete->cover}")) {
                unlink(__DIR__ . "/../../../".CONF_UPLOAD_DIR."/{$categoryDelete->cover}");
                (new Thumb)->flush($categoryDelete->cover);
            }

            $categoryDelete->destroy();
            $this->message->success("Categoria removida com sucesso...")->flash();
            echo json_encode([
                "reload" => true
            ]);
            return;
        }

        $categoryEdit = null;
        if(!empty($data['category_id'])) {
            $categoryId = filter_var($data['category_id'], FILTER_SANITIZE_NUMBER_INT);
            $categoryEdit = (new Category)->findById($categoryId);
        }

        $head = $this->seo->render(
            CONF_SITE_NAME . " | " . "Categoria",
            CONF_SITE_DESC,
            url("/admin"),
            theme("/assets/images/image.jpg", CONF_VIEW_ADMIN),
            false
        );

        echo $this->view->render("widgets/blog/category", [
            "app" => "blog/category",
            "head" => $head,
            "category" => $categoryEdit
        ]);
    }
}