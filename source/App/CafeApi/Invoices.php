<?php 
namespace Source\App\CafeApi;

use Source\Support\Pager;
use Source\Models\CafeApp\AppWallet;
use Source\Models\CafeApp\AppInvoice;
use Source\Models\CafeApp\AppCategory;

class Invoices extends CafeApi
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * List All Invoices
     *
     * @return void
     */
    public function index(): void
    {
        $where = "";
        $params = "";
        $values = $this->headers;

        // Get all wallets
        if(!empty($values["wallet_id"]) && $wallet_id = filter_var($values["wallet_id"], FILTER_VALIDATE_INT)) {
            $where .= " AND wallet_id = :wallet_id";
            $params .= "&wallet_id={$wallet_id}";
        }

        // Type
        $typeList = ["income", "expense", "fixed_income", "fixed_expense"];
        if(!empty($values["type"]) && in_array($values["type"], $typeList) && $type = $values["type"]) {
            $where .= " AND type = :type";
            $params .= "&type={$type}";
        }

        // Status
        $statusList = ["paid", "unpaid"];
        if(!empty($values["status"]) && in_array($values["status"], $statusList) && $status = $values["status"]) {
            $where .= " AND status = :status";
            $params .= "&status={$status}";
        }

        // Get all invoices
        $invoices = (new AppInvoice)->find("user_id = :user_id{$where}", "user_id={$this->user->id}{$params}");

        if(!$invoices->count()) {
            $this->call(404, "not_found", "Nada encontrado para sua pesquisa")->back(['results' => 0]);
            return;
        }

        $page = (!empty($values['page']) ? $values['page'] : 1);
        $pager = new Pager(url("/invoices/"));
        $pager->pager($invoices->count(), 10, $page);

        $response['results'] = $invoices->count();
        $response['page'] = $pager->page();
        $response['pages'] = $pager->pages();

        foreach($invoices->limit($pager->limit())->offset($pager->offset())->order("due_at ASC")->fetch(true) as $invoice) {
            $response["invoices"][] = $invoice->data();
        }

        $this->back($response);
        return;
    }

    public function create(array $data): void
    {
        $request = $this->requestLimit("invoicesCreate", 5, 60);
        if(!$request) {
            return;
        }

        $invoice = new AppInvoice();
        if(!$invoice->launch($this->user, $data)) {
            $this->call(400, "invalid_data", $invoice->message()->getText())->back();
            return;
        }

        $invoice->fixed($this->user, 3);
        $this->back(["invoice" => $invoice->data()]);
    }

    public function read(array $data): void
    {
        if(empty($data['invoice_id']) || !$invoice_id = filter_var($data['invoice_id'], FILTER_VALIDATE_INT)) {
            $this->call(404, "not_found", "Nada encontrado para sua pesquisa")->back();
            return;
        }

        $invoice = (new AppInvoice)->findById("id = :id AND user_id = :user_id", "id={$invoice_id}&user_id={$this->user->id}")->fetch();

        if(!$invoice->count()) {
            $this->call(404, "not_found", "Nada encontrado para sua pesquisa")->back();
            return;
        }

        $response['invoice'] = $invoice_id->data();
        $response['wallet'] = (new AppWallet)->findById($invoice->wallet_id)->data();
        $response['category'] = (new AppCategory)->findById($invoice->category_id)->data();

        $this->back($response);
    }

    public function update(array $data): void
    {
        $data = filter_var_array($data, FILTER_SANITIZE_STRIPPED);
        if(empty($data['invoice_id']) || !$invoice_id = filter_var($data['invoice_id'], FILTER_VALIDATE_INT)) {
            $this->call(404, "not_found", "Nada encontrado para sua pesquisa")->back();
            return;
        }

        $invoice = (new AppInvoice)->findById("id = :id AND user_id = :user_id", "id={$invoice_id}&user_id={$this->user->id}")->fetch();

        if(!$invoice->count()) {
            $this->call(404, "not_found", "Nada encontrado para sua pesquisa")->back();
            return;
        }


        if(!empty($data['wallet_id']) && $wallet_id = filter_var($data['wallet_id'], FILTER_VALIDATE_INT)) {
            $wallet = (new AppWallet)->find("id = :id AND user_id = :user_id", "id={$wallet_id}&user_id={$this->user->id}")->fetch();

            if(!$wallet->count()) {
                $this->call(404, "not_found", "Nada encontrado para sua pesquisa")->back();
                return;
            }
        }

        if(!empty($data['category_id']) && $category_id = filter_var($data['category_id'], FILTER_VALIDATE_INT)) {
            $category = (new AppCategory)->find("id = :id AND user_id = :user_id", "id={$category_id}&user_id={$this->user->id}")->fetch();

            if(!$category->count()) {
                $this->call(404, "not_found", "Nada encontrado para sua pesquisa")->back();
                return;
            }
        }

        if(!empty($data["due_day"])) {
            if($data['due_day'] < 1 || $data['due_day'] > 28) {
                $this->call(400, "invalid_data", "Data inválida")->back();
                return;
            }

            $due_at = date('Y-m', strtotime($invoice->due_at)) . "-" . $data['due_day'];
        }

        $statusList = ["paid", "unpaid"];
        if(!empty($data["status"]) && !in_array($data["status"], $statusList)) {
            $this->call(400, "invalid_data", "Status inválido")->back();
            return;
        }

        $invoice->wallet_id ?? $data['wallet_id'];
        $invoice->category_id ?? $data['category_id'];
        $invoice->due_at ?? $due_at;
        $invoice->status ?? $data["status"];

        if(!$invoice->save()) {
            $this->call(400, "invalid_data", $invoice->message()->getText())->back();
            return;
        }

        $this->back(["invoice" => $invoice->data()]);
    }

    public function delete(array $data): void
    {
        if(empty($data['invoice_id']) || !$invoice_id = filter_var($data['invoice_id'], FILTER_VALIDATE_INT)) {
            $this->call(404, "not_found", "Nada encontrado para sua pesquisa")->back();
            return;
        }

        $invoice = (new AppInvoice)->findById("id = :id AND user_id = :user_id", "id={$invoice_id}&user_id={$this->user->id}")->fetch();

        if(!$invoice->count()) {
            $this->call(404, "not_found", "Nada encontrado para sua pesquisa")->back();
            return;
        }

        $invoice->destroy();

        $this->call(200, "success", "O lançamento foi excluído")->back();
    }
}