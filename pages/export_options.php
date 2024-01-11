<?php require( 'header.php' ); ?>
<?php
    $db_details = $_SESSION['db_details'];
    $domain = $_GET['domain'];
    $dbs = $_GET['dbs'];
?>
<form id="export-options-form" method="post" action="?quickstart=export_now&domain=<?php echo $domain; ?>&dbs=<?php echo $dbs; ?>">
    <div class="toolbar">
        <div class="toolbar-inner">
            <div class="toolbar-buttons">
                <a href="?quickstart=export_details&domain=<?php echo $domain; ?>&dbs=<?php echo $dbs; ?>" class="button button-secondary button-back js-button-back" id="back">
                    <i class="fas fa-arrow-left icon-blue"></i>Back			
                </a>
            </div>
            <div class="toolbar-buttons">
                <button class="button" type="submit" id="continue-button"><i class="fas fa-arrow-right icon-blue"></i>Continue</button>
            </div>
        </div>
    </div>
    <div class="body-reset container">
        <div class="quickstart qs_main export_options">
            <h1>Export options.</h1>
            <legend>Leave all items checked for default export options.</legend>
            <p>
                <input class="export_option" type="checkbox" id="cgi_bin" checked="checked"/>
                <label for="cgi_bin">Include ./cgi-bin folder.</label>
            </p>
            <p>
                <input class="export_option" type="checkbox" id="document_errors" checked="checked"/>
                <label for="document_errors">Include ./document_errors folder.</label>
            </p>
            <p>
                <input class="export_option" type="checkbox" id="nodeapp" checked="checked"/>
                <label for="nodeapp">Include ./nodeapp folder.</label>
            </p>
            <p>
                <input class="export_option" type="checkbox" id="private" checked="checked"/>
                <label for="private">Include ./private folder.</label>
            </p>
            <p>
                <input class="export_option" type="checkbox" id="public_html" checked="checked"/>
                <label for="public_html">Include ./public_html folder.</label>
            </p>
            <p>
                <input class="export_option" type="checkbox" id="exvc" checked="checked"/>
                <label for="exvc">Exclude version control files &amp; folders (.git*, .svn, .hg).</label>
            </p>
            <br/>
            <h3><i class="fas fa-caret-right"></i> Advanced options.</h3>
            <div id="advanced-opt" style="display:none;">
                <p>
                    Additional search and replace controls that will appear when importing. Use these 
                    to replace strings in the database and files; allowing the user to customize
                    the archive before importing.
                </p>
                <br/>
                <table id="advanced-options-table" class="units-table js-units-container">
                    <thead id="adv-options-table" class="units-table-header">
                        <tr class="units-table-row">
                            <th class="units-table-cell">Label</th>
                            <th class="units-table-cell">Value</th>
                            <th class="units-table-cell">Ref. Files</th>
                            <th class="units-table-cell">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
                <br/>
                <form id="add-row-form">
                    <div class="u-mb10">
                        <label for="label-input" class="form-label">
                            Label
                        </label>
                        <input type="text" class="form-control" name="label-input" id="label-input" placeholder="Label">
                    </div>
                    <div class="u-mb10">
                        <label for="value-input" class="form-label">
                            Value
                        </label>
                        <textarea class="form-control" name="value-input" id="value-input" placeholder="Value"></textarea>
                    </div>
                    <div class="u-mb10">
                        <label for="ref-files-input" class="form-label">
                            Reference Files
                        </label>
                        <textarea class="form-control" name="ref-files-input" id="ref-files-input" placeholder="./devstia_databases/*.sql"></textarea>
                    </div>
                    <button class="button" type="button" id="add-row-button">
                        <i class="fas fa-plus icon-blue"></i>Add
                    </button>
                </form>
            </div>
        </div>
        <input type="hidden" id="export_options" name="export_options">
        <input type="hidden" id="export_adv_options" name="export_adv_options">   
    </div>
</form>
<script>
    (function($){
        $(function() {

            // Advanced options, add row to the table
            $('#add-row-button').on('click', function() {
                var label = $('#label-input').val();
                var value = $('#value-input').val();
                var refFiles = $('#ref-files-input').val();
                $('#advanced-options-table tbody').append(
                    '<tr class="units-table-row"><td class="units-table-cell">' + label + 
                    '</td><td class="units-table-cell">' + value + '</td><td class="units-table-cell">' + 
                    refFiles + '</td><td class="units-table-cell adv-trash"><span class="delete-row-button"><i class="fas fa-trash"></i> Delete</span></td></tr>'
                );
                $('#label-input').val('');
                $('#value-input').val('');
                $('#ref-files-input').val('');
                updateAdvOptions();
            });

            // Delete row from the table
            $(document).on('click', '.delete-row-button', function() {
                $(this).closest('tr').remove();
                updateAdvOptions();
            });
            
            // Expand/collapse advanced options
            $('#advanced-opt').hide();
            $('#advanced-opt').prev().on('click', function() {
                $('#advanced-opt').slideToggle();
                $(this).find('i').toggleClass('fa-caret-right fa-caret-down');
            });

            // Checkbox click, update continue button options
            $('.export_option').on('click', function() {
                updateOptions();
            });

            function updateOptions() {
                let options = [];
                $('.export_option').each(function() {
                    if ( $(this).is(':checked') ) {
                        options.push($(this).attr('id'));
                    }
                });
                options = options.join(',');
                $('#export_options').val(options);
            }

            function updateAdvOptions() {
                let tableData = [];
                $('#advanced-options-table tbody tr').each(function() {
                    let row = $(this).find('td').map(function() {
                        return $(this).text();
                    }).get();
                    row.pop();
                    tableData.push(row);
                });
                $('#export_adv_options').val(JSON.stringify(tableData));
            }

            updateOptions();
            updateAdvOptions();
        });
    })(jQuery);
</script>