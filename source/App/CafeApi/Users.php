<?php 
namespace Source\App\CafeApi;

use DateTime;
use Source\Models\CafeApp\AppInvoice;
use Source\Support\Thumb;
use Source\Support\Upload;

class Users extends CafeApi
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * List User Data
     *
     * @return void
     */
    public function index(): void
    {
        $user = $this->user->data();
        $user->photo = CONF_URL_BASE . "/" . CONF_UPLOAD_DIR . "/{$user->photo}";
        unset($user->password, $user->forget);

        $response["user"] = $user;
        $response["user"]->balance = (new AppInvoice)->balance($this->user);
        $this->back($response);
        return;
    }

    public function update(array $data): void
    {
        $request = $this->requestLimit("usersUpdate", 5, 60);
        if(!$request) {
            return;
        }

        $data = filter_var_array($data, FILTER_SANITIZE_STRIPPED);
        $genreList = ["male", "female", "other"];
        if(!empty($data['genre']) && !in_array($data['genre'], $genreList)) {
            $this->call(400, "invalid_data", "Favor informe o gênero como feminino, masculino ou outro")->back();
            return;
        }

        if(!empty($data['datebirth'])) {
            $check = DateTime::createFromFormat("Y-m-d", $data['datebirth']);
            if(!$check || $check->format("Y-m-d") != $data['datebirth']) {
                $this->call(400, "invalid_data", "Favor informe uma data de nascimento válida")->back();
                return;
            }
        }

        $this->user->first_name = ($data['first_name'] ?? $this->user->first_name);
        $this->user->last_name = ($data['last_name'] ?? $this->user->last_name);
        $this->user->genre = ($data['genre'] ?? $this->user->genre);
        $this->user->datebirth = ($data['datebirth'] ?? $this->user->datebirth);
        $this->user->document = ($data['document'] ?? $this->user->document);

        if(!$this->user->save()) {
            $this->call(400, "invalid_data", $this->user->message->getText())->back();
            return;
        }

        $this->index();
    }

    public function photo(): void
    {
        $request = $this->requestLimit("usersPhoto", 3, 1);
        if(!$request) {
            return;
        }

        $photo = (!empty($_FILES['photo']) ? $_FILES : null);
        if(!$photo) {
            $this->call(400, "invalid_data", "Favor informe uma imagem")->back();
            return;
        }

        chdir("../");

        $upload = new Upload;
        $newPhoto = $upload->image($photo, $this->user->fullName(), 600);

        if(!$newPhoto) {
            $this->call(400, "invalid_data", $upload->message()->getText())->back();
            return;
        }

        if($this->user->photo() && $newPhoto != $this->user->photo) {
            unlink(__DIR__ . "/../../../".CONF_UPLOAD_DIR."/{$this->user->photo}");
            (new Thumb)->flush($this->user->photo);
        }

        $this->user->photo = $newPhoto;
        $this->user->save();

        $this->index();
    }
}