<?php

namespace Source\Models\CafeApp;

use Source\Core\Model;
use Source\Models\User;

/**
 * Class AppCreditCard
 * @package Source\Models\CafeApp
 */
class AppCreditCard extends Model
{
    /** @var string */
    private $apiurl;

    /** @var string */
    private $apikey;

    /** @var string */
    private $endpoint;

    /** @var array */
    private $build;

    private $callback;

    /**
     * AppCreditCard constructor.
     */
    public function __construct()
    {
        parent::__construct("app_credit_cards", ["id"], ["user_id", "brand", "last_digits", "cvv", "hash"]);

        $this->apiurl = "https://api.pagar.me/core/v5";
        if (CONF_PAGARME_MODE == "live") {
            $this->apikey = CONF_PAGARME_LIVE;
        } else {
            $this->apikey = CONF_PAGARME_TEST;
        }
    }

    /**
     * @param User $user
     * @return mixed|null
     */
    public function customer(User $user)
    {
        $this->build = [
            "code" => $user->id,
            "name" => $user->fullName(),
            "email" => $user->email,
            "document" => $this->number($user->document),
            "document_type" => "CPF",
            "type" => "individual",
            "phones" => [
                "mobile_phone" => [
                    "country_code" => "55",
                    "area_code" => substr($user->phone, 0, 2),
                    "number" => substr($user->phone, 2, 9)
                ]
            ]
        ];

        $this->endpoint = "/customers";
        $this->post();

        if (empty($this->callback->id)) {
            $this->message->warning("Não foi possível criar uma conta");
            return null;
        }

        return $this->callback()->id;
    }

    /**
     * @param User $user
     * @param string $card_number
     * @param string $card_holder_name
     * @param string $card_exp_date
     * @param string $card_cvv
     * @param string $billing_address_zip_code
     * @param string $billing_address_state
     * @param string $billing_address_city
     * @param string $billing_address_line_1 numero, rua, bairro
     * @param string|null $billing_address_line_2 complemento
     * @param string $billing_address_country
     * @return AppCreditCard|null
     */
    public function creditCard(
        User $user,
        string $card_number,
        string $card_holder_name,
        string $card_exp_date,
        string $card_cvv,
        string $billing_address_zip_code,
        string $billing_address_state,
        string $billing_address_city,
        string $billing_address_line_1,
        string $billing_address_line_2 = null,
        string $billing_address_country = "BR"
    ): ?AppCreditCard {
        $customer_id = $this->customer($user);
        $this->build = [
            "number" => $this->number($card_number),
            "holder_name" => filter_var($card_holder_name, FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            "exp_month" => substr($this->number($card_exp_date), "0", "2"),
            "exp_year" => substr($this->number($card_exp_date), "4", "4"),
            "cvv" => $this->number($card_cvv),
            "billing_address" => [
                "country" => $billing_address_country,
                "zip_code" => $this->number($billing_address_zip_code),
                "state" => $billing_address_state,
                "city" => $billing_address_city,
                "line_1" => $billing_address_line_1,
                "line_2" => $billing_address_line_2
            ],
            "options" => [
                "verify_card" => true
            ]
        ];

        $this->endpoint = "/customers/$customer_id/cards";
        $this->post();

        if (empty($this->callback->id) || $this->callback->status != "active") {
            $this->message->warning("Não foi possível validar o cartão");
            return null;
        }

        $card = $this->find("user_id = :user AND hash = :hash", "user={$user->id}&hash={$this->callback->id}")->fetch();

        if ($card) {
            $card->cvv = $this->number($card_cvv);
            $card->save();
            return $card;
        }

        $this->user_id = $user->id;
        $this->brand = $this->callback->brand;
        $this->last_digits = $this->callback->last_four_digits;
        $this->cvv = $this->number($card_cvv);
        $this->hash = $this->callback->id;
        $this->save();

        return $this;
    }

    /**
     * @param int $code
     * @param string $description 13 chars, letters and spaces
     * @param string $amount_cents
     * @param int $quantity
     * @return AppCreditCard|null
     */
    public function transaction(int $code, string $description, string $amount_cents, int $quantity = 1): ?AppCreditCard
    {
        $user = (new User())->findById($this->user_id);
        $customer_id = $this->customer($user);

        $this->build = [
            "items" => [
                [
                    "code" => $this->number($code),
                    "description" => $this->char($description),
                    "amount" => $this->number($amount_cents),
                    "quantity" => $this->number($quantity)
                ]
            ],
            "customer_id" => $customer_id,
            "payments" => [
                [
                    "payment_method" => "credit_card",
                    "credit_card" => [
                        "card_id" => $this->hash,
                        "statement_descriptor" => $this->char($description),
                        "recurrence" => true,
                        "antifraud_enabled" => false,
                        "installments" => 1
                    ]
                ]
            ]
        ];

        $this->endpoint = "/orders";
        $this->post();

        if (empty($this->callback->status) || $this->callback->status != "paid") {
            $this->message->warning("Pagamento recusado pela operadora");
            return null;
        }

        return $this;
    }

    /**
     * @return mixed
     */
    public function callback()
    {
        return $this->callback;
    }

    /**
     * @param string $number
     * @return string
     */
    private function number(?string $number): string
    {
        return preg_replace("/[^0-9]/", "", $number);
    }

    /**
     * @param string $number
     * @return string
     */
    private function char(?string $char, int $limit = 13): string
    {
        $charsReplaced = preg_replace("/[^a-zA-Z ]/", "", $char);
        return (strlen($charsReplaced)) >= $limit ? substr($charsReplaced, 0, $limit - 1) : $charsReplaced;
    }

    /**
     * CURL POST
     */
    private function post()
    {
        $url = $this->apiurl . $this->endpoint;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($this->build));
        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            [
                "Authorization: Basic " . base64_encode($this->apikey),
                "Accept: application/json",
                "Content-Type: application/json"
            ]
        );

        $this->callback = json_decode(curl_exec($ch));
        curl_close($ch);
    }
}