<?php require( 'header.php' ); ?>
<?php
    // Validate the job_id
    $job_id = $_GET['job_id'];
    if ( $hcpp->quickstart->is_job_valid( $job_id ) === false ) {
        header( 'Location: ?quickstart=main' );
        exit;
    }

    // Get the manifest
    $manifest = $hcpp->quickstart->get_job_data( $job_id, 'manifest' );
    if ( $manifest === false ) {
        header( 'Location: ?quickstart=main' );
        exit;
    }

    // Get the selected databases
    $dbs = [];
    if ( !isset( $_GET['dbs'] ) ) {
        header( 'Location: ?quickstart=main' );
        exit;
    }else{
        $dbs = $_GET['dbs'];
    }

    // Cull unselected databases from the manifest
    $db_details = $manifest['databases'];
    $db_selected = [];
    foreach ( $db_details as $db ) {
        if ( strpos( $dbs, $db['DATABASE'] ) === false ) {
            continue;
        }
        $db_selected[] = $db;
    }
    $manifest['databases'] = $db_selected;
    $hcpp->set_job_data( $job_id, 'manifest', $manifest );
    $domain = $manifest['domain'];
    $user = $manifest['user'];
?>
<form id="export-options-form" method="post" action="?quickstart=export_now&domain=<?php echo $domain; ?>&dbs=<?php echo $dbs; ?>&job_id=<?php echo $job_id; ?>">
    <div class="toolbar">
        <div class="toolbar-inner">
            <div class="toolbar-buttons">
                <a href="?quickstart=export_details&domain=<?php echo $domain; ?>&dbs=<?php echo $dbs; ?>&job_id=<?php echo $job_id; ?>" class="button button-secondary button-back js-button-back" id="back-button">
                    <i tabindex="300" class="fas fa-arrow-left icon-blue"></i>Back			
                </a>
            </div>
            <div class="toolbar-buttons">
                <button tabindex="200" class="button" type="submit" id="continue-button"><i class="fas fa-arrow-right icon-blue"></i>Continue</button>
            </div>
        </div>
    </div>
    <div class="body-reset container">
        <div class="quickstart qs_export_options">
            <h1>Export Options</h1>
            <p>Set options or add additional file or folder paths to include or exclude
            from your export archive. <a href="https://devstia.com/documentation/quickstart/import-or-export-a-website#export-options" target="_blank">Learn more.</a></p>
            <br>
            <style>
                .form-group {
                    display: flex;
                    align-items: flex-start;
                    width: 100%; /* Ensure the container takes full width */
                    box-sizing: border-box; /* Include padding and border in the element's total width and height */
                    overflow: hidden; /* Prevent overflow */
                }

                .control-group {
                    display: flex;
                    flex-direction: column;
                    margin: 0; /* Reset margin */
                    padding: 0; /* Reset padding */
                }

                .file-path-group {
                    flex: 0 0 50%;
                    min-width: 0; /* Prevent overflow */
                }

                .action-select-group {
                    flex: 0 0 15%;
                    min-width: 90px; /* Prevent overflow */
                }

                .add-button-group {
                    min-width: 0; /* Prevent overflow */
                }

                .ml-2 {
                    margin-left: 10px;
                }

                .form-label, .form-control, .form-select, .button {
                    box-sizing: border-box;
                }

                #addButton {
                    margin-top: 30px;
                }
                table#export-options-table thead tr th.delete {
                    width: 20%;
                }
                span.delete-row-button {
                    cursor: pointer;
                }
                th.file-path {
                    width: 50%;
                }
                th.drop-down {
                    text-align: left;
                    padding-left: 45px;
                }
                td.file-path {
                    padding: 8px 12px;
                }
                td.dropdown {
                    padding-top: 6px;
                }
                td.dropdown select {
                    font-size: smaller; 
                    padding: 4px 15px 2px 5px; 
                    width: 80px;
                }
                #advanced-options-table tbody tr td.units-table-cell {
                    padding: 10px;
                }
                .dropdown .fas {
                    margin: 7px 5px 0 0;
                }
                .dropdown .text-success {
                    color: rgb(0, 180, 0);;
                }
                .dropdown .text-danger {
                    color: rgb(180, 0, 0);
                }
                .dropdown {
                    display: inline-flex;
                }
            </style>
            <div class="u-mb10 form-group d-flex align-items-center tabs">
                <div class="control-group file-path-group">
                    <label for="filePathInput" class="form-label">
                        File/Folder Path
                    </label>
                    <input type="text" class="form-control" name="filePathInput" id="filePathInput" placeholder="./public_html" tabindex="100">
                </div>
                <div class="control-group action-select-group ml-2">
                    <label for="actionSelect" class="form-label">
                        Action
                    </label>
                    <select class="form-select" name="actionSelect" id="actionSelect" tabindex="100">
                        <option value="include">Include</option>
                        <option value="exclude">Exclude</option>
                    </select>
                </div>
                <div class="control-group add-button-group ml-2">
                    <button class="button" type="button" id="addButton">
                        <i tabindex="100" class="fas fa-plus icon-blue"></i>Add
                    </button>
                </div>
            </div>
            <div class="u-mb10">
                <table id="export-options-table" class="units-table js-units-container">
                    <thead id="export-options-thead" class="options-table-head units-table-header" style="color:white;">
                        <tr class="units-table-row">
                            <th class="units-table-cell file-path">File/Folder Path</th>
                            <th class="units-table-cell drop-down">Action</th>
                            <th class="units-table-cell delete"></th>
                        </tr>
                    </thead>
                    <tbody id="listbox">
                    </tbody>
                </table>
            </div>
            <input type="hidden" id="export_includes" name="export_includes" value="">
            <input type="hidden" id="export_excludes" name="export_excludes" value="">
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    const listbox = document.getElementById('listbox');
                    const addButton = document.getElementById('addButton');
                    const filePathInput = document.getElementById('filePathInput');
                    const actionSelect = document.getElementById('actionSelect');
                    const exportIncludes = document.getElementById('export_includes');
                    const exportExcludes = document.getElementById('export_excludes');

                    const updateHiddenFields = () => {
                        const included = [];
                        const excluded = [];
                        const items = listbox.getElementsByClassName('listbox-item');
                        for (let item of items) {
                            let filePath = item.querySelector('.file-path').textContent;
                            if (filePath.startsWith('./')) {
                                filePath = filePath.substring(2);
                            }
                            const action = item.querySelector('.dropdown select').value;
                            const icon = item.querySelector('.dropdown i');

                            if (action === 'include') {
                                included.push(filePath);
                                icon.className = 'fas fa-check-circle text-success';
                            } else {
                                excluded.push(filePath);
                                icon.className = 'fas fa-times-circle text-danger';
                            }
                        }
                        exportIncludes.value = included.join(',');
                        exportExcludes.value = excluded.join(',');
                    };

                    const addItem = (filePath, action) => {
                        if (filePath) {
                            if (filePath.startsWith('./')) {
                                filePath = filePath.substring(2);
                            }
                            const newItem = document.createElement('tr');
                            newItem.classList.add('units-table-row');
                            newItem.classList.add('listbox-item');
                            const iconClass = action === 'include' ? 'fas fa-check-circle text-success' : 'fas fa-times-circle text-danger';
                            newItem.innerHTML = `
                                <td class="file-path">./${filePath}</td>
                                <td class="dropdown">
                                    <i class="${iconClass}"></i>
                                    <select class="form-select" name="actionSelect" id="actionSelect" tabindex="100">
                                        <option value="include" ${action === 'include' ? 'selected' : ''}>Include</option>
                                        <option value="exclude" ${action === 'exclude' ? 'selected' : ''}>Exclude</option>
                                    </select>
                                </td>
                                <td class="delete">
                                    <span tabindex="100" class="delete-row-button delete-export-option"><i class="fas fa-trash"></i> Delete</span>
                                </td>
                            `;
                            listbox.appendChild(newItem);
                            updateHiddenFields();
                        }
                    };

                    addButton.addEventListener('click', () => {
                        addItem(filePathInput.value, actionSelect.value);
                        filePathInput.value = '';
                        actionSelect.value = 'include';
                        filePathInput.focus(); // Set focus back to the text input field
                    });

                    addButton.addEventListener('keydown', (event) => {
                        if (event.key === 'Enter' || event.key === ' ') {
                            event.preventDefault();
                            addItem(filePathInput.value, actionSelect.value);
                            filePathInput.value = '';
                            actionSelect.value = 'include';
                            filePathInput.focus(); // Set focus back to the text input field
                        }
                    });

                    listbox.addEventListener('click', (event) => {
                        if (event.target.classList.contains('delete-export-option')) {
                            const item = event.target.closest('.listbox-item');
                            if (item) {
                                item.remove();
                                updateHiddenFields();
                            }
                        }
                    });

                    listbox.addEventListener('keydown', (event) => {
                        if ((event.key === 'Enter' || event.key === ' ') && event.target.classList.contains('delete-export-option')) {
                            event.preventDefault();
                            const item = event.target.closest('.listbox-item');
                            if (item) {
                                item.remove();
                                updateHiddenFields();
                            }
                        }
                    });

                    listbox.addEventListener('change', (event) => {
                        if (event.target.tagName === 'SELECT') {
                            updateHiddenFields();
                        }
                    });

                    window.addItemToListbox = addItem;
                    <?php
                        $private_folder = "/home/" . $_SESSION['user'] . "/web/" . $domain . "/private";
                        $exvc_checked = 'checked';
                        $script = '';
                        try {
                            if ( file_exists( $private_folder . '/devstia_manifest.json' ) ) {
                                $content = file_get_contents( $private_folder . '/devstia_manifest.json' );
                                $json = json_decode( $content, true );
                                $export_options = $json['export_options'];
                                $export_options = explode( ',', $export_options );
                                $export_includes = $json['export_includes'];
                                $export_includes = explode( ',', $export_includes );
                                $export_excludes = $json['export_excludes'];
                                $export_excludes = explode( ',', $export_excludes );
                                foreach ($export_includes as $include) {
                                    $script .= 'window.addItemToListbox("' . $include . '", "include");' . "\n";
                                }
                                foreach ($export_excludes as $exclude) {
                                    $script .= 'window.addItemToListbox("' . $exclude . '", "exclude");' . "\n";
                                }
                                if ( in_array( 'exvc', $export_options ) ) {
                                    $exvc_checked = 'checked';
                                }else{
                                    $exvc_checked = '';
                                }
                            }else{
                                $subfolders = $hcpp->quickstart->get_domain_folders( $user, $domain );
                                $excluded = ['stats', 'logs', 'private', '.vscode'];
                                foreach ( $subfolders as $subfolder ) {
                                    $folderName = basename( $subfolder );
                                    if ( !in_array( $folderName, $excluded ) ) {
                                        $script .= 'window.addItemToListbox("' . $folderName . '", "include");' . "\n";
                                    }
                                }
                            }
                        }catch(Exception $e) {
                            $script .= 'console.log("' . htmlspecialchars( $e->getMessage(), ENT_QUOTES, 'UTF-8' ) . '");' . "\n";
                        }
                        echo $script;
                    ?>
                });
            </script>
            
            <br>
            <h3 tabindex="100"><i class="fas fa-caret-right"></i> Advanced Options</h3>
            <div id="advanced-opt" style="display:none;">
                <p>
                    Additional search and replace controls to appear when importing. Use these 
                    to replace strings in the database and files; allowing user customizations.
                </p>
                <p>
                    Learn more on <a href="https://devstia.com/docs/export-advanced-options" target="_blank">Devstia's website</a>.
                </p>
                <br>
                <div class="tabs">
                    <form id="add-row-form">
                        <div class="u-mb10">
                            <label for="label-input" class="form-label">
                                Label
                            </label>
                            <input type="text" class="form-control" name="label-input" id="label-input" placeholder="Label" tabindex="100">
                        </div>
                        <div class="u-mb10">
                            <label for="value-input" class="form-label">
                                Value
                            </label>
                            <textarea class="form-control" name="value-input" id="value-input" placeholder="Value" tabindex="100"></textarea>
                        </div>
                        <div class="u-mb10">
                            <label for="ref-files-input" class="form-label">
                                Reference Files
                            </label>
                            <textarea class="form-control" name="ref-files-input" id="ref-files-input" placeholder="./public_html/index.html" tabindex="100"></textarea>
                        </div>
                        <button class="button" type="button" id="add-row-button">
                            <i tabindex="100" class="fas fa-plus icon-blue"></i>Add
                        </button>
                    </form>
                </div>
                <div class="u-mb10" style="padding-top:10px;">
                    <table id="advanced-options-table" class="units-table js-units-container">
                        <thead id="adv-options-table" class="options-table-head units-table-header" style="color:white;">
                            <tr class="units-table-row">
                                <th class="units-table-cell">Label</th>
                                <th class="units-table-cell">Value</th>
                                <th class="units-table-cell">Ref. Files</th>
                                <th class="units-table-cell"> </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                // Pre-populate the export and advanced options with any existing values if private/devstia_manifest.json exists
                                if ( file_exists( $private_folder . '/devstia_manifest.json' ) ) {
                                    try {
                                        foreach ( $json['export_adv_options'] as $option ) {
                                            $label = htmlspecialchars( $option['label'], ENT_QUOTES | ENT_HTML5, 'UTF-8' );
                                            $value = htmlspecialchars( $option['value'], ENT_QUOTES | ENT_HTML5, 'UTF-8' );
                                            $ref_files = htmlspecialchars( implode( "\n", $option['ref_files'] ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
                                            echo '<tr class="units-table-row">';
                                            echo '    <td class="units-table-cell">' . $label . '</td>';
                                            echo '    <td class="units-table-cell">' . $value . '</td>';
                                            echo '    <td class="units-table-cell">' . $ref_files . '</td>';
                                            echo '    <td class="units-table-cell adv-trash"><span tabindex="100" class="delete-row-button"><i class="fas fa-trash"></i> Delete</span></td>';
                                            echo '</tr>';
                                        }
                                    }catch (Exception $e) {
                                        echo 'Caught exception: ',  $e->getMessage(), "\n";
                                    }
                                }
                            ?>
                        </tbody>
                    </table>
                </div>
                <br>
                <p>
                   Optional post process setup script; use this to write a script to run after import.
                </p>
                <br>
                <div class="u-mb10">
                    <label for="setup-input" class="form-label">
                        Setup Script
                    </label>
                    <textarea class="form-control" name="setup-input" id="setup-input" placeholder="" tabindex="100" rows="10" style="font-family: monospace; margin: 0;"><?php
                            $private_folder = "/home/" . $_SESSION['user'] . "/web/" . $domain . "/private";
                            if ( file_exists( $private_folder . '/devstia_setup.sh' ) ) {
                                $content = file_get_contents( $private_folder . '/devstia_setup.sh' );
                                echo htmlspecialchars( $content, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
                            }
                    ?></textarea>
                </div>

                <br>
                <div class="u-mb10">
                    <p>
                        <input class="export_option" type="checkbox" id="exvc" <?php echo $exvc_checked; ?> tabindex="100"/>
                        <label for="exvc">Exclude version control files &amp; folders (.git*, .svn, .hg).</label>
                    </p>
                </div>
            </div>
        </div>
        <input type="hidden" id="export_options" name="export_options">
        <input type="hidden" id="export_adv_options" name="export_adv_options">   
    </div>
</form>
<script>
    (function($){
        $(function() {

            // Escape encode our any html
            function escapeHtml(unsafe) {
                return unsafe
                    .replace(/&/g, "&amp;")
                    .replace(/</g, "&lt;")
                    .replace(/>/g, "&gt;")
                    .replace(/\//g, "&#x2F;")
                    .replace(/"/g, "&quot;")
                    .replace(/'/g, "&#039;");
            }

            // Advanced options, add row to the table
            $('#add-row-button').on('click', function() {
                const label = escapeHtml($('#label-input').val());
                const value = escapeHtml($('#value-input').val());
                const refFiles = escapeHtml($('#ref-files-input').val());
                $('#advanced-options-table tbody').append(
                    '<tr class="units-table-row"><td class="units-table-cell">' + label + 
                    '</td><td class="units-table-cell">' + value + '</td><td class="units-table-cell">' + 
                    refFiles + '</td><td class="units-table-cell adv-trash"><span tabindex="100" class="delete-row-button"><i class="fas fa-trash"></i> Delete</span></td></tr>'
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
            $('.delete-row-button').on('keydown', function(e) {
                if (e.keyCode == 13 || e.keyCode == 32) {
                    $(this)[0].click();
                }
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

                    // Label the columns, make ref_files an array
                    row = {
                        'label': row[0],
                        'value': row[1],
                        'ref_files': row[2].split('\n'),
                    };
                    tableData.push(row);
                });
                $('#export_adv_options').val(JSON.stringify(tableData));
            }
            updateOptions();
            updateAdvOptions();
        });
    })(jQuery);
</script>