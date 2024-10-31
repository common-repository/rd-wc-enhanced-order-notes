jQuery(document).ready(function() {
    RDWCEONManager.load();
});

function RDWCEONManager() {}

RDWCEONManager.load = function() {
    RDWCEONManager.getOrderNoteTemplateData();
    RDWCEONManager.addTemplateCategory();
    RDWCEONManager.removeTemplateCategory();
    RDWCEONManager.saveTemplate();
    RDWCEONManager.removeTemplate();
    RDWCEONManager.buildTemplateList();
    RDWCEONManager.setupUseTemplate();
    RDWCEONManager.hideReviewUpgradeNotice();
}

RDWCEONManager.ajaxPost = function(data, onComplete) {
    data['action'] = 'rdwceon_do_ajax';
    data['_ajax_nonce'] = RDWCEONSettings.nonces.ajax_nonce;
    jQuery.post(ajaxurl, data, onComplete);
}

RDWCEONManager.hideReviewUpgradeNotice = function() {
    jQuery('#wpbody-content').on('click', '#rdwceon-review-upgrade-notice .rdwceon-hide-notice', function(event) {
        event.preventDefault();
        jQuery('#rdwceon-review-upgrade-notice .notice-dismiss').click();
        let data = {
            method: 'hide_review_upgrade_notice',
        }

        RDWCEONManager.ajaxPost(data, function(response) {});
    });
}

RDWCEONManager.getOrderNoteTemplateData = function() {
    jQuery(document.body).on('click', '.rdwceon-show-edit-template-modal', function(event) {
        event.preventDefault();
        let button = jQuery(this);
        jQuery(button).addClass('disabled')
            .prop('disabled', true);
        jQuery('.rdwceon_templates_wrapper').block({
            message: null,
            overlayCSS: {
                background: '#fff',
                opacity: 0.6
            }
        });
        let templateId = parseInt(jQuery(this).attr('data-template-id'));
        let data = {
            method: 'get_order_note_template_data',
            template_id: templateId,
        }
        RDWCEONManager.ajaxPost(data, function(response) {
            jQuery(button).removeClass('disabled')
                .prop('disabled', false);
            jQuery('.rdwceon_templates_wrapper').unblock();
            if (response.success) {
                jQuery('.rdwceon_templates_wrapper').WCBackboneModal({
                    template: 'wc-modal-rdwceon-template',
                    variable: response.data,
                });
            }
        });
    });
}

RDWCEONManager.addTemplateCategory = function() {
    jQuery(document.body).on('click', '.rdwceon-template-new-category-button', function() {
        let button = jQuery(this);
        jQuery(button).addClass('disabled')
            .prop('disabled', true);
        jQuery('.rdwceon-template-category-list-table').block({
            message: null,
            overlayCSS: {
                background: '#fff',
                opacity: 0.6
            }
        });
        jQuery(this).siblings('.error')
            .addClass('hidden');
        jQuery(this).parent().removeClass('form-invalid');
        let data = {
            method: 'add_template_category',
            category_name: jQuery('.rdwceon-template-new-category').val(),
        }
        RDWCEONManager.ajaxPost(data, function(response) {
            jQuery(button).removeClass('disabled')
                .prop('disabled', false);
            jQuery('.rdwceon-template-category-list-table').unblock();
            if (response.success) {
                let html = '';
                html += '<tr valign="top">';
                html +=     '<td>';
                html +=         '<div>';
                html +=             '<input id="rdwceon-template-category-radio-' + response.data.term_id + '" type="radio" checked="checked" name="rdwceon_template_category" value="' + response.data.term_id + '">';
                html +=             '<label for="rdwceon-template-category-radio-' + response.data.term_id + '">' + response.data.name + '</label>';
                html +=          '</div>';
                html +=     '</td>';
                html +=     '<td>';
                html +=         '<button type="button" data-term-id="' + response.data.term_id + '" class="rdwceon-template-category-remove" title="' + RDWCEONSettings.i18n.delete + '">';
                html +=             '<span class="dashicons dashicons-remove"></span>';
                html +=         '</button>';
                html +=     '</td>';
                html += '</tr>';
                jQuery('.rdwceon-template-category-list-table input[name=rdwceon_template_category]').each(function() {
                    jQuery(this).removeAttr('checked')
                        .prop('checked', false);
                });
                jQuery('.rdwceon-template-category-list-table tbody').append(html);
            } else {
                jQuery(button).siblings('.error')
                .removeClass('hidden');
                jQuery(button).parent().addClass('form-invalid');
            }
        });
    });
}

RDWCEONManager.removeTemplateCategory = function() {
    jQuery(document.body).on('click', '.rdwceon-template-category-remove', function() {
        let button = jQuery(this);
        jQuery(button).addClass('disabled')
            .prop('disabled', true);
        jQuery('.rdwceon-template-category-list-table').block({
            message: null,
            overlayCSS: {
                background: '#fff',
                opacity: 0.6
            }
        });
        let data = {
            method: 'remove_template_category',
            term_id: jQuery(this).attr('data-term-id'),
        }
        RDWCEONManager.ajaxPost(data, function(response) {
            jQuery(button).removeClass('disabled')
                .prop('disabled', false);
            jQuery('.rdwceon-template-category-list-table').unblock();
            if (response.success) {
                jQuery(button).parentsUntil('tr').parent().remove();
            }
        });
    });
}

RDWCEONManager.saveTemplate = function() {
    jQuery(document.body).on('click', '.rdwceon-template-save', function() {
        let button = jQuery(this);
        jQuery(button).addClass('disabled')
            .prop('disabled', true);
        jQuery('.rdwceon-template-notes-table').block({
            message: null,
            overlayCSS: {
                background: '#fff',
                opacity: 0.6
            }
        });
        jQuery('.rdwceon-template-notes-table .error')
            .addClass('hidden');
        let data = {
            method: 'save_template',
            template_id: jQuery(this).attr('data-template-id'),
            title: jQuery('.rdwceon-pro-template-title').val(),
            type: jQuery('.rdwceon-template-type').val(),
            note: jQuery('#rdwceon-template-note').val(),
            term_id: jQuery('.rdwceon-template-category-list-table input[name=rdwceon_template_category]:checked').first().val(),
        }
        RDWCEONManager.ajaxPost(data, function(response) {
            jQuery(button).removeClass('disabled')
                .prop('disabled', false);
            jQuery('.rdwceon-template-notes-table').unblock();
            if (response.success) {
                RDWCEONManager.buildTemplateList();
                jQuery('.rdwceon-template .modal-close').first().click();
            } else {
                for (let a = 0; a < response.data.errors.length; a++) {
                    jQuery('.rdwceon-template-notes-table label[for=' + response.data.errors[a] + ']')
                        .parent()
                        .removeClass('hidden');
                }
            }
        });
    });
}

RDWCEONManager.removeTemplate = function() {
    jQuery(document.body).on('click', '.rdwceon-template-remove', function() {
        let button = jQuery(this);
        jQuery(button).addClass('disabled')
            .prop('disabled', true);
        jQuery('.rdwceon_templates_wrapper').block({
            message: null,
            overlayCSS: {
                background: '#fff',
                opacity: 0.6
            }
        });
        let data = {
            method: 'remove_template',
            template_id: jQuery(this).attr('data-template-id'),
        }
        RDWCEONManager.ajaxPost(data, function(response) {
            jQuery(button).removeClass('disabled')
                .prop('disabled', false);
            jQuery('.rdwceon_templates_wrapper').unblock();
            if (response.success) {
                RDWCEONManager.buildTemplateList();
            }
        });
    });
}

RDWCEONManager.buildTemplateList = function() {
    if (jQuery('.rdwceon_templates_wrapper').length > 0) {
        jQuery('.rdwceon_templates_wrapper').block({
            message: null,
            overlayCSS: {
                background: '#fff',
                opacity: 0.6
            }
        });
        let data = {
            method: 'build_template_list',
        }
        RDWCEONManager.ajaxPost(data, function(response) {
            jQuery('.rdwceon_templates_wrapper').unblock();
            jQuery('.rdwceon_templates_wrapper .rdwceon_templates tbody').html(response);
        });
    }
}

RDWCEONManager.setupUseTemplate = function() {
    if (jQuery('#rdwceon_order_note_template').length > 0) {
        jQuery('#rdwceon_order_note_template').selectWoo({
            templateResult: RDWCEONManager.templateSelectWoo,
            escapeMarkup: function(m) { return m; }
        }).addClass('enhanced')
        .on('change', RDWCEONManager.changeTemplate);
    }
}

RDWCEONManager.changeTemplate = function(event, value) {
    jQuery('#add_order_note').addClass('disabled')
        .prop('disabled', true);
    jQuery('.rdwceon-add-note').block({
        message: null,
        overlayCSS: {
            background: '#fff',
            opacity: 0.6
        }
    });
    let element = jQuery(this);
    let type = jQuery(element).find('option:selected').first().attr('data-type');
    let templateId = jQuery(element).val();
    if (type == 'customer') {
        jQuery('#order_note_type').val('customer');
    }
    if (type == 'private') {
        jQuery('#order_note_type').val('');
    }
    jQuery('#order_note_type').trigger('change');
    let data = {
        method: 'get_template_content',
        template_id: templateId,
    }
    RDWCEONManager.ajaxPost(data, function(response) {
        jQuery('#add_order_note').removeClass('disabled')
            .prop('disabled', false);
        jQuery('#add_order_note').val(response);
        jQuery('.rdwceon-add-note').unblock();
    });
}

RDWCEONManager.templateSelectWoo = function(template) {
    let type = jQuery(template.element).attr('data-type');
    if (!template.id) { 
        return template.text;
    }
    if (type == 'private') {
        return '<span class="dashicons dashicons-lock"></span>&nbsp;&nbsp;' + template.text;
    }
    if (type == 'customer') {
        return '<span class="dashicons dashicons-businessperson"></span>&nbsp;&nbsp;' + template.text;
    }
    return template.text;
}