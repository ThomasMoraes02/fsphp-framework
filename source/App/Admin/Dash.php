<?php 
namespace Source\App\Admin;

use Source\Models\Auth;
use Source\Models\Post;
use Source\Models\User;
use Source\Models\Category;
use Source\Models\Report\Online;
use Source\Models\CafeApp\AppPlan;
use Source\Models\CafeApp\AppSubscription;

class Dash extends Admin
{
    public function __construct()
    {
        parent::__construct();
    }

    public function dash()
    {
        redirect("/admin/dash/home");
    }

    public function home(?array $data): void
    {
        // Real Time Access
        if(!empty($data['refresh'])) {
            $list = null;
            $items = (new Online())->findByActive();
            if($items) {
                foreach($items as $item) {
                    $list[] = [
                        "dates" => date_fmt($item->created_at, "H/i") . " - " . date_fmt($item->updated_at, "H/i"),
                        "user" => ($item->user ? $item->user()->full_name() : "Guest User"),
                        "pages" => $item->pages,
                        "url" => $item->url
                    ];
                }
            }

            echo json_encode([
                "count" => (new Online())->findByActive(true),
                "list" => $list
            ]);
            return;
        }

        $head = $this->seo->render(
            CONF_SITE_NAME . " | Dashboard",
            CONF_SITE_DESC,
            url("/admin"),
            theme("/assets/images/image.jpg", CONF_VIEW_ADMIN),
            false
        );

        echo $this->view->render("widgets/dash/home", [
            "head" => $head,
            "app" => "dash",
            "control" => (object)[
                "subscribers" => (new AppSubscription)->find("pay_status = :s", "s=active")->count(),
                "plans" => (new AppPlan)->find("status = :s", "s=active")->count(),
                "recurrence" => (new AppSubscription)->recurrence()
            ],
            "blog" => (object)[
                "posts" => (new Post)->find("status = 'post'")->count(),
                "drafts" => (new Post)->find("status = 'draft'")->count(),
                "categories" => (new Category)->find("type = 'post'")->count()
            ],
            "users" => (object)[
                "users" => (new User)->find('level < 5')->count(),
                "admins" => (new User)->find('level >= 5')->count(),
            ],
            "online" => (new Online())->findByActive(),
            "onlineCount" => (new Online())->findByActive(true),
        ]);
    }

    public function logoff()
    {
        $this->message->success("Você saiu com sucesso {$this->user->first_name}.")->flash();

        Auth::logout();
        redirect("/admin/login");
    }
}