<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

use App\User;
use App\Supply_order_item;

class Supply_order extends Model
{
    /* Получить имя сотрудника, создавшего заказ */
    public function get_creator_name(){
        $user = User::find($this->creator_id);
        return $user->general_name;
    }

    /* Получить дату создания заказа */
    public function get_creation_date(){
        $datetime = $this->created_at;
        $dt = new \DateTime($datetime);
        $date = $dt->format('d.m.Y');
        return $date;
    }

    /* Получить кол-во позиций в заказе */
    public function get_entries_count(){
        return Supply_order_item::where('supply_order_id', $this->id)->count();
    }

    /* Получить кол-во единиц в заказе */
    public function get_items_count(){
        $supply_order_items = Supply_order_item::where('supply_order_id', $this->id)->get();
        $general_count = 0;
        foreach($supply_order_items as $supply_order_entry){
            $general_count += $supply_order_entry->number;
        }
        return $general_count;
    }
}