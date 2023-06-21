<?php 
namespace Source\Models\Faq;

use Source\Core\Model;

class Channel extends Model
{
    public function __construct()
    {
        parent::__construct("faq_channels", ["id"], ["channel", "description"]);
    }

    /**
     * @return Question
     */
    public function questions(): Question
    {
        return (new Question)->find("channel_id = :id",":id={$this->id}");
    }
}