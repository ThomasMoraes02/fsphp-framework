<?php 
namespace Source\App\Admin;

use Source\Models\Post;
use Source\Support\Pager;

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
        
    }

    public function categories(?array $data): void
    {
        
    }

    public function category(?array $data): void
    {
        
    }
}