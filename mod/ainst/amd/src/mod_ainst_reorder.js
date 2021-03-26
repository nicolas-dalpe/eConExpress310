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
 * Manage the assessment and section reorder.
 *
 * @module     mod_ainst/reorder
 * @package    mod_ainst
 * @copyright  2020 Knowledge One Inc. {@link http://knowledgeone.ca}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 define(['jquery', 'core/str', 'core/notification', 'core/sortable_list', 'core/ajax'],
    function($, str, notification, SortableList, ajax) {

    return {

        /**
         * Function being called by the js_call_amd()
         * @param str type The item to reorder. assignment or section
         */
        init: function(type) {

            // Selector of the list item to reorder.
            var sortable_list = '#items-list';

            // The name of the order column in database.
            var order_field = '';

            $('.btn').click(function() {

                // Hide all notification in case the timeout is not exhausted and there is a new reorder.
                hideNotification(".alert");

                // Use the right order database field.
                if (type == 'assignment') {
                    order_field = 'inst_order';
                } else if (type == 'section') {
                    order_field = 'section_order';
                }

                // When the item is dropped, get the list of assessment and their new order.
                var ordered_list = {};
                $(sortable_list + ' > li').each(function(i, e) {
                    ordered_list['item_' + i] = {
                        'id': $(e).attr('data-sectionname'),
                        [order_field]: i
                    };
                });

                // Send the new order to the server.
                var promises = ajax.call([{
                    methodname: 'mod_ainst_reorder' + type,
                    args: {[type]: ordered_list}
                }]);

                // Process the server response.
                promises[0].done(function() {

                    // Get the notification message to display.
                    str.get_strings([
                        {'key': 'reorder_' + type + '_success', component: 'ainst'}
                    ]).done(function(s) {
                        // Display the Notification.
                        notification.addNotification({message: s[0], type: "success"});
                        // Hide Notification after x seconds.
                        hideNotificationQueue("#user-notifications .alert");
                    });
                }).fail(function() {
                    str.get_strings([
                        {'key': 'reorder_' + type + '_error', component: 'ainst'}
                    ]).done(function(s) {
                        notification.addNotification({message: s[0], type: "error"});
                    });
                });

            });

            var sectionName = function(element) {
                return $.Deferred().resolve(element.attr('data-sectionname'));
            };

            // Sort sections.
            var sortSections = new SortableList(sortable_list, {
                // We need a specific handler here because otherwise the handler from embedded activities triggers section move.
                moveHandlerSelector: sortable_list + ' > li > span > [data-drag-type=move]'
            });
            sortSections.getElementName = sectionName;

            /**
             * hideNotificationQueue()
             * Hide notification after x milliseconds
             * str e The element selector to hide
             * int t The timeout length in millisecond
             */
            function hideNotificationQueue(e, t = 10000) {

                // Make sure we have a timeout value.
                t = parseInt(t, 10);
                if (isNaN(t)) {
                    t = 10000;
                }

                // Hide notification after t milliseconds
                setTimeout(function() {hideNotification(e, t);}, t);
            }

             /**
             * hideNotification()
             * Hide notification immediately
             * str e The element selector to hide
             */
            function hideNotification(e) {
                // Cache element
                var el = $(e);

                // Make sure we have an element to hide.
                if (el.length) {

                    // Hide and remove the notification.
                    el.hide('slow', function() {el.remove();});
                }
            }
        } // init()
    };
});
