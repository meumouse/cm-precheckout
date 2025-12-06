jQuery(document).ready(function($) {
    let courseIndex = $('#courses-container .course-item').length;
    let stoneIndex = $('#stones-container .stone-item').length;

    // Media uploader
    $(document).on('click', '.media-upload-btn', function(e) {
        e.preventDefault();
        
        const button = $(this);
        const mediaContainer = button.closest('.media-upload');
        const inputField = mediaContainer.find('.media-input, .media-input-url');
        const preview = mediaContainer.find('.media-preview');
        
        const frame = wp.media({
            title: 'Selecionar Imagem',
            button: {
                text: 'Usar esta imagem'
            },
            multiple: false
        });
        
        frame.on('select', function() {
            const attachment = frame.state().get('selection').first().toJSON();
            
            if (inputField.hasClass('media-input')) {
                inputField.val(attachment.id);
                preview.html('<img src="' + attachment.url + '" style="max-width: 100px; height: auto;" />');
            } else {
                inputField.val(attachment.url);
                preview.html('<img src="' + attachment.url + '" style="max-width: 100px; height: auto;" />');
            }
        });
        
        frame.open();
    });

    // Add new course
    $('#add-course').on('click', function() {
        const template = $('#course-template').html();
        const newCourse = template.replace(/{{index}}/g, courseIndex);
        $('#courses-container').append(newCourse);
        courseIndex++;
    });

    // Remove course
    $(document).on('click', '.remove-course', function() {
        if (confirm('Tem certeza que deseja remover este curso?')) {
            $(this).closest('.course-item').remove();
        }
    });

    // Add new stone
    $('#add-stone').on('click', function() {
        const template = $('#stone-template').html();
        const newStone = template.replace(/{{index}}/g, stoneIndex);
        $('#stones-container').append(newStone);
        stoneIndex++;
    });

    // Remove stone
    $(document).on('click', '.remove-stone', function() {
        if (confirm('Tem certeza que deseja remover esta pedra?')) {
            $(this).closest('.stone-item').remove();
        }
    });
});