<?php 
namespace Source\Models\CafeApp;

use Source\Core\Model;
use Source\Models\User;

class AppOrder extends Model
{
    public function __construct()
    {
        parent::__construct("app_orders", ["id"], ["user_id", "card_id", "subscription_id", "transaction", "amount", "status"]);
    }

    /**
     * Updates the AppOrder object with credit card information and saves it to the database.
     *
     * @param User $user the User object of the customer
     * @param AppCreditCard $card the AppCreditCard object used for the transaction
     * @param AppSubscription $sub the AppSubscription object associated with the transaction
     * @param AppCreditCard $tr the AppCreditCard object containing the transaction details
     * @return AppOrder the updated AppOrder object
     */

    public function byCreditCard(User $user, AppCreditCard $card, AppSubscription $sub, AppCreditCard $tr): AppOrder
    {
        $this->user_id = $user->id;
        $this->card_id = $card->id;
        $this->subscription_id = $sub->id;
        $this->transaction = $tr->callback()->id;
        $this->amount = number_format($tr->callback()->amount / 100, 2, ",", ".");
        $this->status = $tr->callback()->status;
        $this->save();
        return $this;
    }

    public function creditCard()
    {
        return (new AppCreditCard)->findById($this->card_id);
    }
}