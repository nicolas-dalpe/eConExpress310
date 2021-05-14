<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Client for a user.
 *
 * @package    local_mootivated
 * @copyright  2018 Mootivation Technologies Corp.
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_mootivated;
defined('MOODLE_INTERNAL') || die();

use coding_exception;
use curl;
use moodle_exception;
use moodle_url;
use stdClass;

/**
 * Client for a user to dialogue with the server.
 *
 * @package    local_mootivated
 * @copyright  2018 Mootivation Technologies Corp.
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class client_user extends client {

    /** @var object The login info. */
    protected $logininfo;

    /**
     * Constructor.
     *
     * @param string $host The host.
     * @param object $logininfo The login info.
     * @param object $lang The Accept-Language header content.
     */
    public function __construct($host, stdClass $logininfo, $lang = null) {
        parent::__construct($host);
        $this->logininfo = $logininfo;
        if ($lang) {
            $this->headers['Accept-Language'] = $lang;
        }
        $this->headers['X-Moot-Auth'] = $logininfo->api_key;
    }

    /**
     * Get coin history.
     *
     * @return array
     */
    public function get_coin_history() {
        return $this->request('/user/coins/history', null, 'GET');
    }

    /**
     * Get the leaderboard.
     *
     * @return object
     */
    public function get_leaderboard() {
        return $this->request('/school/get_leaderboard', null, 'GET');
    }

    /**
     * Get the purchases.
     *
     * @return array
     */
    public function get_purchases($state = null) {
        $args = [];
        if (!empty($state)) {
            $args['state'] = $state;
        }
        $data = $this->request('/me/purchases', $args);
        return array_map(function($purchase) use ($data) {
            $purchase->item->image_url = $this->logininfo->file_storage_url . $purchase->item->image_url;
            return $purchase;
        }, $data);
    }


    /**
     * Get the purchases that have been redeemed.
     *
     * @return array
     */
    public function get_redeemed_purchases() {
        return $this->get_purchases('redeemed');
    }

    /**
     * Get the purchased items.
     *
     * @return array
     */
    public function get_purchased_items() {
        $data = $this->request('/store/get_purchased_items');

        $quantities = array_reduce($data->purchased_item_ids, function($carry, $id) {
            if (!isset($carry[$id])) {
                $carry[$id] = 0;
            }
            $carry[$id] += 1;
            return $carry;
        }, []);

        return array_map(function($item) use ($data, $quantities) {
            $item->redeempending = count(array_filter($data->redeem_requests, function($request) use ($item) {
                return $request->item_id == $item->id;
            })) > 0;
            $item->quantityowned = isset($quantities[$item->id]) ? $quantities[$item->id] : 0;
            $item->image_url = $this->logininfo->file_storage_url . $item->image_url;
            return $item;
        }, $data->item_definitions);
    }

    /**
     * Return the redemption URL.
     *
     * @param string $purchaseid The purchase ID.
     * @param string $returnurl The return URL.
     * @return string
     */
    public function get_redemption_url($purchaseid, moodle_url $returnurl) {
        return $this->request('/store/get_redemption_url', [
            'purchase_id' => $purchaseid,
            'return_url' => $returnurl->out(false)
        ]);
    }

    /**
     * Return the redemptions URL.
     *
     * When redeeming multiple items at once.
     *
     * @param string $purchaseids The purchase IDs.
     * @param string $returnurl The return URL.
     * @return string
     */
    public function get_redemptions_url($purchaseids, moodle_url $returnurl) {
        return $this->request('/store/get_redemptions_url', [
            'purchase_ids' => $purchaseids,
            'return_url' => $returnurl->out(false)
        ]);
    }

    /**
     * Return the store items.
     *
     * @return array
     */
    public function get_store_items() {
        $data = $this->request('/store/get');
        return array_map(function($item) {
            $item->image_url = $this->logininfo->file_storage_url . $item->image_url;
            return $item;
        }, $data->store_items);
    }

    /**
     * Get the SVS leaderboard.
     *
     * @return object
     */
    public function get_svs_leaderboard() {
        return $this->request('/account/get_svs_leaderboard', null, 'GET');
    }

    /**
     * Place bid.
     *
     * @param string $itemid The item ID.
     * @param int $bid The bid, excluding handling fee.
     * @return void
     */
    public function place_bid($itemid, $bid) {
        return $this->request('/store/place_bid', [
            'item_id' => $itemid,
            'bid' => $bid,
        ]);
    }

    /**
     * Purchase an item.
     *
     * @param string $itemid The item ID.
     * @return void
     */
    public function purchase_item($itemid) {
        $result = $this->request('/store/purchase_item', [
            'store_item_id' => $itemid
        ]);

        // Return the purchase when it's returned.
        return !empty($result->result) && is_object($result->result) && !empty($result->result->id) ? $result->result : null;
    }

    /**
     * Purchase items.
     *
     * @param array $items Contains id and quantity.
     */
    public function purchase_items($items) {
        $result = $this->request('/store/purchase_items', array_map(function($item) {
            $item = (object) $item;
            if (empty($item->id) || empty($item->quantity)) {
                throw new coding_exception('Missing id or quantity for purchasing item');
            };
            return ['item_id' => $item->id, 'quantity' => $item->quantity];
        }, $items));
        return array_map(function($purchase) {
            $purchase->item->image_url = $this->logininfo->file_storage_url . $purchase->item->image_url;
            return $purchase;
        }, $result->result);
    }

    /**
     * Redeem request
     *
     * @param string $itemid The item ID.
     * @param string $message A message.
     * @return mixed
     */
    public function redeem_request($itemid, $message) {
        $data = $this->request('/store/redeem_request', [
            'item_id' => $itemid,
            'message' => $message
        ]);
        if (empty($data->code) || $data->code !== 200) {
            throw new moodle_exception('Redemption request failed', 'local_mootivated', '', null, json_encode($data));
        }
        return $data;
    }

    /**
     * Self redemption.
     *
     * @param string $itemid The item ID.
     * @return mixed
     */
    public function self_redeem($itemid) {
        $data = $this->request('/store/self_redeem', [
            'item_id' => $itemid
        ]);
        if (!empty($data->code) && $data->code !== 200) {
            throw new moodle_exception('Redemption failed', 'local_mootivated', '', null, json_encode($data));
        }
        return $data;
    }
}
