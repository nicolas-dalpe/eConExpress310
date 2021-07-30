// This is to extend the Hybrid question type
// It's binding some events on custom elements on the file manager events to have a custom UI

M.qtype_hybrid = {};

M.qtype_hybrid.init = function(Y, options) {
    var currentImageName = '';

    var requiredFiles = Y.all('.js-edit-file.is-required');

    // Listen to add ajax requests on the page
    var origOpen = XMLHttpRequest.prototype.open;
    XMLHttpRequest.prototype.open = function() {
        this.addEventListener('load', function() {
            // Deleting an existing image
            if (this.responseURL.indexOf('?action=delete') > -1) {
                Y.one('.js-edit-file img[src*="' + currentImageName + '"]').get('parentNode').remove();
                currentImageName = '';
            }

            // Retrieving all images from File manager
            if (this.responseURL.indexOf('draftfiles_ajax.php?action=list') > -1) {
                var response = JSON.parse(this.responseText);

                // Order by date
                var responseOrderedDate = response.list.sort((a,b) => (a.datecreated > b.datecreated) ? 1 : ((b.datecreated > a.datecreated) ? -1 : 0))
                

                // Remove all placeholders to rebuilt it with the list from AJAX call
                var placeholders = Y.all('.qtype_hybrid_placeholder');
                placeholders.each(function (placeholder) {
                    placeholder.remove();
                });


                // If Filecount is higher than placeholders
                var placeholdersWrapper = Y.one('.qtype_hybrid_attachments_placeholders_wrapper');
                for (var i = 0; i < response.filecount; i++) {
                    var classes = 'qtype_hybrid_placeholder js-edit-file';
                    if (i === 0) {
                        classes += ' is-required';
                    }
                    placeholdersWrapper.append("<div class='" + classes + "'><img src='" + responseOrderedDate[i].url + "' /></div>");
                }

                // If there's no file yet, add the upload box with the text Upload file +
                if (response.filecount === 0) {
                    placeholdersWrapper.append("<div class='qtype_hybrid_placeholder is-required js-add-file'><i class='icon fa fa-plus' aria-hidden='true'></i></div>");
                }

                // Edit an existing file, will trigger the click of when you click on an image in the File manager
                // It will open a lightbox where you can Delete or update the image
                // If the slot is empty, it will add a new file
                var editFiles = Y.all('.js-edit-file');
                editFiles.each(function (editFile) {
                    editFile.on('click', function(node) {
                        if (editFile.one('img')) {
                            var fileSrc = editFile.one('img').get('src');
                            var fileSrcArray = fileSrc.split('/');

                            var thumbnailFileManager = Y.one('.fp-thumbnail img[title*="' + decodeURI(fileSrcArray[fileSrcArray.length-1]) + '"]');
                            
                            thumbnailFileManager.simulate('click');
                            currentImageName = fileSrcArray[fileSrcArray.length-1];
                        } else {
                            var addNewButton = Y.one('.fp-btn-add .btn');
                            addNewButton.simulate('click');
                            setTimeout(function() {
                                // set user preferences to upload
                                var uploadTab = Y.one('.file-picker .fp-repo-icon[src*="repository_upload"]').get('parentNode');
                                uploadTab.simulate('click');
                            }, 500);
                        }
                        
                    });
                });

                // Add a new file, will trigger the click of the Add new file from File manager
                var addFiles = Y.all('.js-add-file');
                addFiles.each(function (addFile) {
                    addFile.on('click', function(node) {
                        var addNewButton = Y.one('.fp-btn-add .btn');
                        addNewButton.simulate('click');
                        setTimeout(function() {
                            // set user preferences to upload
                            var uploadTab = Y.one('.file-picker .fp-repo-icon[src*="repository_upload"]').get('parentNode');
                            uploadTab.simulate('click');
                        }, 500);
                    });
                });


            }
            
        });
        origOpen.apply(this, arguments);
    };
};

M.qtype_hybrid.init(Y);
