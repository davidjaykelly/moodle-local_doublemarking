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
 * JavaScript for double marking grading functionality.
 *
 * @module     local_doublemarking/grading
 * @copyright  2025 Your Name <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([
    'jquery',
    'core/ajax',
    'core/str',
    'core/templates',
    'core/notification',
    'core/modal_factory',
    'core/modal_events',
    'mod_assign/grading_panel'
], function(
    $,
    Ajax,
    Str,
    Templates,
    Notification,
    ModalFactory,
    ModalEvents,
    GradingPanel
) {

    /**
     * Class constructor for the DoubleMarkingGrading.
     *
     * @class
     * @param {string} selector The CSS selector to use for this module's interaction
     */
    var DoubleMarkingGrading = function(selector) {
        this.rootElement = $(selector);
        this.contextId = this.rootElement.data('contextid');
        this.assignmentId = this.rootElement.data('assignmentid');
        this.gradeThreshold = 0;
        this.blindSetting = 0;
        this.marksHidden = false;
        this.init();
    };

    /**
     * Initialize the module.
     *
     * @method init
     */
    DoubleMarkingGrading.prototype.init = function() {
        // Load necessary configuration
        this.loadConfiguration();
        
        // Initialize event listeners
        this.initEvents();
        
        // Enhance the standard grading panel with double marking elements
        this.enhanceGradingPanel();
    };

    /**
     * Load double marking configuration.
     *
     * @method loadConfiguration
     */
    DoubleMarkingGrading.prototype.loadConfiguration = function() {
        var self = this;

        // Get double marking settings from the server
        var promises = Ajax.call([{
            methodname: 'local_doublemarking_get_settings',
            args: {
                assignmentid: self.assignmentId
            }
        }]);

        promises[0].done(function(response) {
            self.gradeThreshold = parseFloat(response.gradedifferencethreshold) || 10;
            self.blindSetting = parseInt(response.blindsetting) || 0;
            self.marksHidden = Boolean(parseInt(response.markshidden)) || false;
            
            // Update UI based on settings
            self.updateUI();
        }).fail(function(error) {
            Notification.exception(error);
        });
    };
    
    /**
     * Initialize event listeners.
     *
     * @method initEvents
     */
    DoubleMarkingGrading.prototype.initEvents = function() {
        var self = this;

        // Handle ratification form submission
        this.rootElement.on('submit', '.local-doublemarking-ratification form', function(e) {
            e.preventDefault();
            self.submitRatification($(this));
            return false;
        });

        // Handle grade choice selection in ratification panel
        this.rootElement.on('change', 'input[name="gradechoice"]', function() {
            self.handleGradeChoiceChange($(this));
        });

        // Handle visibility toggle for marker identities
        this.rootElement.on('click', '.toggle-marker-visibility', function(e) {
            e.preventDefault();
            self.toggleMarkerVisibility();
            return false;
        });

        // Listen for grade changes from the standard grading panel
        $(document).on('gradechanged', function(e, data) {
            if (data && data.userid && data.grade !== undefined) {
                self.handleGradeChange(data.userid, data.grade);
            }
        });
    };
    
    /**
     * Toggle the visibility of marker identities.
     *
     * @method toggleMarkerVisibility
     */
    DoubleMarkingGrading.prototype.toggleMarkerVisibility = function() {
        var markerIdentities = this.rootElement.find('.marker-identity');
        var toggleButton = this.rootElement.find('.toggle-marker-visibility');
        
        if (markerIdentities.hasClass('hidden')) {
            // Show marker identities
            markerIdentities.removeClass('hidden');
            
            Str.get_string('hidemarkers', 'local_doublemarking').done(function(string) {
                toggleButton.text(string);
            });
        } else {
            // Hide marker identities
            markerIdentities.addClass('hidden');
            
            Str.get_string('showmarkers', 'local_doublemarking').done(function(string) {
                toggleButton.text(string);
            });
        }
    };
    
    /**
     * Handle a grade change event.
     *
     * @method handleGradeChange
     * @param {number} userId The ID of the user whose grade changed
     * @param {number} grade The new grade value
     */
    DoubleMarkingGrading.prototype.handleGradeChange = function(userId, grade) {
        var self = this;
        
        // Get current marker allocations and update UI
        this.getMarkerAllocation(userId).then(function(data) {
            // Check if grade difference threshold is exceeded
            if (data.gradestatus.thresholdexceeded) {
                // Show warning notification if grade difference exceeds threshold
                Str.get_strings([
                    {key: 'gradingwarning', component: 'local_doublemarking'},
                    {key: 'gradingwarning_critical', component: 'local_doublemarking', param: data.gradestatus.gradedifference}
                ]).done(function(strings) {
                    Notification.alert(strings[0], strings[1]);
                });
            }
            
            // Refresh the grading panel to show updated information
            self.refreshGradingPanel(userId);
        }).catch(function(error) {
            Notification.exception(error);
        });
    };
    
    /**
     * Refresh the grading panel for a specific user.
     *
     * @method refreshGradingPanel
     * @param {number} userId The ID of the user to refresh
     */
    DoubleMarkingGrading.prototype.refreshGradingPanel = function(userId) {
        var self = this;
        
        // Get updated marker allocation information
        this.getMarkerAllocation(userId).then(function(data) {
            // Re-render our template
            return Templates.render('local_doublemarking/grading_panel_extension', data);
        }).then(function(html) {
            // Replace the existing extension with the updated one
            var existingExtension = self.rootElement.find('.local-doublemarking-extension');
            if (existingExtension.length) {
                existingExtension.replaceWith(html);
                self.initRatificationControls();
            } else {
                // If it doesn't exist yet, insert it
                var gradingPanelContent = self.rootElement.find('[data-region="grade-panel-content"]');
                if (gradingPanelContent.length) {
                    gradingPanelContent.prepend(html);
                    self.initRatificationControls();
                }
            }
        }).catch(function(error) {
            Notification.exception(error);
        });
    };
    
    /**
     * Update the UI based on settings.
     * 
     * @method updateUI
     */
    DoubleMarkingGrading.prototype.updateUI = function() {
        // Apply blind marking settings
        if (this.blindSetting > 0) {
            this.rootElement.addClass('blind-marking');
            if (this.blindSetting === 2) {
                this.rootElement.addClass('double-blind');
            }
        }
        
        // Apply mark visibility settings
        if (this.marksHidden) {
            this.rootElement.addClass('marks-hidden');
        }
    };
    
    /**
     * Handle errors from AJAX requests.
     * 
     * @method handleError
     * @param {Object} error The error object
     */
    DoubleMarkingGrading.prototype.handleError = function(error) {
        // Log error for debugging
        if (window.console && window.console.log) {
            console.log('Double Marking Error:', error);
        }
        
        // Show error notification
        Notification.exception(error);
    };
    
    /**
     * Enhance the standard grading panel with double marking elements.
     *
     * @method enhanceGradingPanel
     */
    DoubleMarkingGrading.prototype.enhanceGradingPanel = function() {
        var self = this;

        // Wait for the standard grading panel to load
        this.waitForGradingPanel().then(function() {
            // Get current user ID
            var currentUserId = self.getCurrentUserId();
            if (!currentUserId) {
                return;
            }

            // Get marker allocation information
            self.getMarkerAllocation(currentUserId).then(function(data) {
                // Render our template and inject it into the grading panel
                return Templates.render('local_doublemarking/grading_panel_extension', data);
            }).then(function(html) {
                // Insert the rendered template into the grading panel
                var gradingPanelContent = self.rootElement.find('[data-region="grade-panel-content"]');
                if (gradingPanelContent.length) {
                    gradingPanelContent.prepend(html);
                    self.initRatificationControls();
                }
            }).catch(function(error) {
                Notification.exception(error);
            });
        });
    };

    /**
     * Wait for the standard grading panel to load.
     *
     * @method waitForGradingPanel
     * @return {Promise}
     */
    DoubleMarkingGrading.prototype.waitForGradingPanel = function() {
        var self = this;
        return new Promise(function(resolve) {
            // Check if the panel is already loaded
            if (self.rootElement.find('[data-region="grade-panel-content"]').length) {
                resolve();
                return;
            }

            // Otherwise, wait for the panel to load
            var checkInterval = setInterval(function() {
                if (self.rootElement.find('[data-region="grade-panel-content"]').length) {
                    clearInterval(checkInterval);
                    resolve();
                }
            }, 200);

            // Set a timeout to avoid infinite waiting
            setTimeout(function() {
                clearInterval(checkInterval);
                resolve();
            }, 10000);
        });
    };

    /**
     * Get the current user ID from the page.
     *
     * @method getCurrentUserId
     * @return {number|null} The current user ID or null if not found
     */
    DoubleMarkingGrading.prototype.getCurrentUserId = function() {
        var userIdElement = this.rootElement.find('[data-region="user-info"]');
        if (userIdElement.length) {
            return parseInt(userIdElement.data('userid'));
        }
        return null;
    };

    /**
     * Get marker allocation information for a user.
     *
     * @method getMarkerAllocation
     * @param {number} userId The user ID to get allocation for
     * @return {Promise}
     */
    DoubleMarkingGrading.prototype.getMarkerAllocation = function(userId) {
        var self = this;
        return new Promise(function(resolve, reject) {
            var promises = Ajax.call([{
                methodname: 'local_doublemarking_get_allocation',
                args: {
                    assignmentid: self.assignmentId,
                    userid: userId
                }
            }]);

            promises[0].done(function(response) {
                // Prepare data for the template
                var data = {
                    doublemarking: true,
                    studentid: userId,
                    cmid: self.assignmentId,
                    allocation: {
                        hasallocation: Boolean(response.marker1 || response.marker2),
                        marker1: response.marker1,
                        marker2: response.marker2,
                        ismarker1: response.ismarker1,
                        ismarker2: response.ismarker2,
                        blindsetting: self.blindSetting,
                        markshidden: self.marksHidden,
                        canviewmarkers: response.canviewmarkers
                    },
                    gradestatus: {
                        marker1grade: response.marker1grade,
                        marker2grade: response.marker2grade,
                        marker1timemodified: response.marker1timemodified,
                        marker2timemodified: response.marker2timemodified,
                        canviewgrades: response.canviewgrades,
                        gradedifference: response.gradedifference,
                        thresholdexceeded: response.thresholdexceeded,
                        isratifier: response.isratifier
                    },
                    ratification: {
                        isratifier: response.isratifier,
                        marker1grade: response.marker1grade,
                        marker2grade: response.marker2grade,
                        thresholdexceeded: response.thresholdexceeded,
                        studentid: userId,
                        cmid: self.assignmentId,
                        finalgrade: response.finalgrade,
                        ratificationcomment: response.ratificationcomment
                    }
                };
                resolve(data);
            }).fail(function(error) {
                reject(error);
            });
        });
    };

    /**
     * Initialize ratification panel controls.
     *
     * @method initRatificationControls
     */
    DoubleMarkingGrading.prototype.initRatificationControls = function() {
        var self = this;
        var panel = this.rootElement.find('.local-doublemarking-ratification');
        
        if (panel.length) {
            // Set initial state of the finalgrade field based on selected grade choice
            var selectedChoice = panel.find('input[name="gradechoice"]:checked').val();
            self.updateFinalGradeField(selectedChoice);
            
            // Set up initial form validation
            panel.find('form').on('submit', function() {
                return self.validateRatificationForm($(this));
            });
        }
    };

    /**
     * Handle grade choice change in ratification panel.
     *
     * @method handleGradeChoiceChange
     * @param {Object} radioButton The radio button that changed
     */
    DoubleMarkingGrading.prototype.handleGradeChoiceChange = function(radioButton) {
        var choice = radioButton.val();
        var form = radioButton.closest('form');
        var finalGradeField = form.find('#finalgrade');
        
        // Update the final grade field based on the selection
        this.updateFinalGradeField(choice);
        
        // Enable/disable the final grade field based on the selection
        if (choice === 'custom') {
            finalGradeField.prop('disabled', false).focus();
        } else {
            finalGradeField.prop('disabled', true);
        }
    };

    /**
     * Update the final grade field based on the selected grade choice.
     *
     * @method updateFinalGradeField
     * @param {string} choice The selected grade choice
     */
    DoubleMarkingGrading.prototype.updateFinalGradeField = function(choice) {
        var panel = this.rootElement.find('.local-doublemarking-ratification');
        var finalGradeField = panel.find('#finalgrade');
        var marker1Grade = parseFloat(panel.find('#useMarker1').closest('label').text().match(/\d+(\.\d+)?/)[0]);
        var marker2Grade = parseFloat(panel.find('#useMarker2').closest('label').text().match(/\d+(\.\d+)?/)[0]);
        
        switch (choice) {
            case 'marker1':
                finalGradeField.val(marker1Grade);
                break;
            case 'marker2':
                finalGradeField.val(marker2Grade);
                break;
            case 'average':
                var average = ((marker1Grade + marker2Grade) / 2).toFixed(2);
                finalGradeField.val(average);
                break;
            case 'custom':
                // Leave the current value
                break;
        }
    };

    /**
     * Validate the ratification form before submission.
     *
     * @method validateRatificationForm
     * @param {Object} form The form to validate
     * @return {boolean} Whether the form is valid
     */
    DoubleMarkingGrading.prototype.validateRatificationForm = function(form) {
        var finalGradeField = form.find('#finalgrade');
        var finalGrade = finalGradeField.val();
        
        // Check if the final grade is a valid number
        if (isNaN(finalGrade) || finalGrade === '') {
            Notification.alert(
                Str.get_string('error', 'core'),
                Str.get_string('invalidgrade', 'local_doublemarking')
            );
            finalGradeField.focus();
            return false;
        }
        
        return true;
    };

    /**
     * Submit the ratification form.
     *
     * @method submitRatification
     * @param {Object} form The form to submit
     */
    DoubleMarkingGrading.prototype.submitRatification = function(form) {
        var self = this;
        var formData = form.serializeArray();
        var data = {};
        
        // Convert form data array to object
        $.each(formData, function(i, field) {
            data[field.name] = field.value;
        });
        
        // Add assignment ID if not already in the form
        data.assignmentid = this.assignmentId;
        
        // Send the ratification data to the server
        var promises = Ajax.call([{
            methodname: 'local_doublemarking_save_ratification',
            args: data
        }]);
        
        // Show loading indicator
        var loadingElement = $('<div class="loading-icon"><i class="fa fa-spinner fa-spin"></i></div>');
        form.append(loadingElement);
        form.find('button[type="submit"]').prop('disabled', true);
        
        promises[0].done(function(response) {
            // Remove loading indicator
            loadingElement.remove();
            form.find('button[type="submit"]').prop('disabled', false);
            
            // Show success message
            Str.get_strings([
                {key: 'success', component: 'core'},
                {key: 'ratificationsaved', component: 'local_doublemarking'}
            ]).done(function(strings) {
                Notification.alert(strings[0], strings[1]);
                
                // Refresh the grading panel
                self.refreshGradingPanel(data.studentid);
            });
        }).fail(function(error) {
            // Remove loading indicator
            loadingElement.remove();
            form.find('button[type="submit"]').prop('disabled', false);
            
            // Show error message
            Notification.exception(error);
        });
    };
    
    // Create a singleton instance of the module
    return {
        /**
         * Initialize the module with a specific selector.
         *
         * @method init
         * @param {string} selector The CSS selector for the grading panel
         * @return {DoubleMarkingGrading} A new instance of the module
         */
        init: function(selector) {
            return new DoubleMarkingGrading(selector);
        }
    };
});

