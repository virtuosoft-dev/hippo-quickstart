<?php
/**
 * Quickstart plugin adds the Quickstart tab for an easy-to-use guide
 * and quick website setup.
 * 
 * @version 1.0.0
 * @license GPL-3.0
 * @link https://github.com/virtuosoft-dev/hcpp-quickstart
 * 
 */

 if ( ! class_exists( 'Quickstart') ) {
    class Quickstart {
        /**
         * Constructor, listen for the render events
         */
        public function __construct() {
            global $hcpp;
            $hcpp->quickstart = $this;
            $hcpp->add_action( 'hcpp_invoke_plugin', [ $this, 'hcpp_invoke_plugin' ] );
            $hcpp->add_action( 'hcpp_render_body', [ $this, 'hcpp_render_body' ] );
            $hcpp->add_action( 'hcpp_render_panel', [ $this, 'hcpp_render_panel' ] );
            $hcpp->add_action( 'hcpp_rebooted', [ $this, 'hcpp_rebooted' ] );
            $hcpp->add_action( 'hcpp_head', [ $this, 'hcpp_head' ] );
        }

        // Clean up the devstia_*
        public function hcpp_rebooted( $args ) {

            // Clean up /tmp/devstia_* files
            shell_exec('rm -rf /tmp/devstia_*');

            // Cycle through all users and remove /home/user/tmp/devstia_export_* folders
            global $hcpp;
            $users = $hcpp->run( "list-users json" );
            foreach( $users as $user => $details) {
                $command = "find /home/$user/tmp -maxdepth 1 -type d | egrep 'devstia_export_'";
                $folders = array_filter(explode("\n", shell_exec($command)));
                foreach( $folders as $folder ) {
                    $command = "rm -rf $folder";
                    shell_exec($command);
                }
            }
            return $args;
        }

        // Run elevated commands from the plugin
        public function hcpp_invoke_plugin( $args ) {
            if ( $args[0] == 'quickstart_site_details' ) return $this->quickstart_site_details( $args );
            if ( $args[0] == 'quickstart_export_zip' ) return $this->quickstart_export_zip( $args );
            if ( $args[0] == 'quickstart_export_status' ) return $this->quickstart_export_status( $args );
            if ( $args[0] == 'quickstart_export_cancel' ) return $this->quickstart_export_cancel( $args );
            if ( $args[0] == 'quickstart_import_status' ) return $this->quickstart_import_status( $args );
            if ( $args[0] == 'quickstart_import_cancel' ) return $this->quickstart_import_cancel( $args );
            if ( $args[0] == 'quickstart_import_file' ) return $this->quickstart_import_file( $args );
            if ( $args[0] == 'quickstart_import_now' ) return $this->quickstart_import_now( $args );
            if ( $args[0] == 'quickstart_import_result' ) return $this->quickstart_import_result( $args );
            return $args;
        }

        // Return the result of the import process
        public function quickstart_import_result( $args ) {
            $import_pid = $args[1];
            $job_id = $args[2];
            $result_file = "/tmp/devstia_import_$job_id.result";
            if ( file_exists( $result_file ) ) {
                $content = file_get_contents( $result_file );
                unlink( $result_file );
                echo $content;

                if ( strpos( $content, '"status":"error"' ) !== false || strpos( $content, '"status":"finished"' ) !== false ) {

                    // Clean up
                    shell_exec('rm -rf /tmp/devstia_import_' . $job_id . '*');
                }
            }else{
                $status = shell_exec('ps -p ' . $import_pid);
                if (strpos($status, $import_pid) !== false) {
                    echo json_encode( [ 'status' => 'running', 'message' => 'Please wait. Importing website.' ] );
                }else{
                    echo json_encode( [ 'status' => 'error', 'message' => 'Import failed. Please try again.' ] );

                    // Clean up
                    shell_exec('rm -rf /tmp/devstia_import_' . $job_id . '*');
                }
            }
            return $args;
        }
 
        // Now import the given folder
        public function quickstart_import_now( $args ) {
            $job_id = $args[1];
            $request_file = "/tmp/devstia_import_$job_id.json";
            $result_file = "/tmp/devstia_import_$job_id.result";
            if ( !file_exists( $request_file ) ) {
                $this->report_status( $result_file, 'Request file not found.', 'error' );
                return $args;
            }
            $content = file_get_contents( $request_file );
            unlink ( $request_file );
            $request = json_decode( $content, true );

            // Allow plugins to modify the request
            global $hcpp;
            $request = $hcpp->do_action( 'quickstart_import_now_request', $request );
            $import_folder = $request['import_folder'];

            // Get the manifest file
            $manifest = $import_folder . '/devstia_manifest.json';
            if ( !file_exists( $manifest ) ) {
                $this->report_status( $result_file, 'Manifest file not found.', 'error' );
                return $args;
            }
            
            // Parse the manifest file
            try {
                $content = file_get_contents( $manifest );
                $manifest = json_decode( $content, true );
                $manifest = $hcpp->do_action( 'quickstart_import_now_manifest', $manifest ); // Allow plugin mods
                $orig_user = $manifest['user'];
                $orig_domain = $manifest['domain'];
                $orig_aliases = $manifest['alias'];
                $proxy_ext = $manifest['proxy_ext'];
                $backend = $manifest['backend'];
            }catch( Exception $e ) {
                $this->report_status( $result_file, 'Error parsing manifest file.', 'error' );
                return $args;
            }

            // Create the domain
            $this->report_status( $result_file, 'Please wait. Creating domain.' );
            $new_user = $request['user'];
            $new_domain = $request['v_domain'];
            $new_aliases = str_replace( "\r\n", ",", $request['v_aliases'] );
            $details = $hcpp->run('list-user-ips ' . $new_user . ' json');
            $first_ip = null;
            foreach ( $details as $ip => $ip_details ) {
                $first_ip = $ip;
                break;
            }
            $command = "add-web-domain $new_user $new_domain $first_ip no \"$new_aliases\" $proxy_ext";
            $result = $hcpp->run( $command );
            if ( $result != '' ) {
                $this->report_status( $result_file, $result, 'error' );
                return $args;
            }

            // Wait up to 15 seconds for public_html/index.html to be created
            $dest_folder = '/home/' . $new_user . '/web/' . $new_domain;
            for ( $i = 0; $i < 15; $i++ ) {
                if ( file_exists( $dest_folder . '/public_html/index.html' ) ) break;
                sleep(1);
            }
            if ( !is_dir( $dest_folder . '/public_html' ) ) {
                $this->report_status( $result_file, 'Error timeout awaiting domain creation. ' . $dest_folder, 'error' );
                return $args;
            }

            // Copy all subfolders in the import folder
            $this->report_status( $result_file, 'Please wait. Copying files.' );
            $folders = array_filter(glob($import_folder . '/*'), 'is_dir');
            $command = "rm -f $dest_folder/public_html/index.html ; ";
            foreach( $folders as $folder ) {
                $subfolder = $hcpp->getRightMost( $folder, '/' );
                $command .= __DIR__ . '/abcopy ' . $folder . '/ ' . $dest_folder . "/$subfolder/ ; ";
                $command .= "chown -R $new_user:$new_user " . $dest_folder . "/$subfolder/ ; ";
                if ( $subfolder == 'public_html' ) {
                    $command .= "chown $new_user:www-data " . $dest_folder . "/$subfolder/ ; ";
                }
            }
            $command = $hcpp->do_action( 'quickstart_import_copy_files', $command ); // Allow plugin mods
            shell_exec( $command );

            // Prepare aliases
            $new_aliases = explode( ',', $new_aliases );
            $orig_aliases = explode( ',', $orig_aliases );
            if ( count( $new_aliases ) != count( $orig_aliases ) ) {
                $this->report_status( $result_file, 'Number of aliases does not match original for substitution.', 'error' );
                return $args;
            }

            // Create the databases
            $orig_dbs = $manifest['databases'];
            if ( is_array( $orig_dbs ) && !empty( $orig_dbs ) ) {
                foreach( $orig_dbs as $db ) {

                    // Get the original database details
                    $orig_dbuser = $db['DBUSER'];
                    $orig_db = $db['DATABASE'];
                    $orig_password = $db['DBPASSWORD'];
                    $orig_type = $db['TYPE'];
                    $orig_charset = $db['CHARSET'];
                    $ref_files = $db['ref_files'];

                    // Generate new credentials and new database
                    $db_name = $hcpp->nodeapp->random_chars(5);
                    $db_password = $hcpp->nodeapp->random_chars(20);
                    $command = "add-database $new_user $db_name $db_name $db_password $orig_type localhost $orig_charset";
                    $db_name = $new_user . '_' . $db_name;
                    $this->report_status( $result_file, "Please wait. Creating database: $db_name" );
                    $result = $hcpp->run( $command );

                    // Search and replace credentials in ref_files
                    foreach( $ref_files as $file ) {
                        $file = $dest_folder . '/' . $hcpp->delLeftMost( $file, '/' );
                        try {
                            $this->search_replace_file( 
                                $file, 
                                [$orig_db, $orig_password], 
                                [$db_name, $db_password] 
                            );
                        }catch( Exception $e ) {
                            $this->report_status( $result_file, $e->getMessage(), 'error' );
                            return $args;
                        }
                    }

                    // Search and replace domain, user path, and aliases in db sql files
                    $db_sql_file = $dest_folder . '/devstia_databases/' . $db['DATABASE'] . '.sql';
                    $searches = [$orig_domain, "/home/$orig_user"];
                    $replaces = [$new_domain, "/home/$new_user"];
                    $searches = array_merge( $searches, $orig_aliases );
                    $replaces = array_merge( $replaces, $new_aliases );
                    try {
                        $this->search_replace_file( $db_sql_file, $searches, $replaces );
                    }catch( Exception $e ) {
                        $this->report_status( $result_file, $e->getMessage(), 'error' );
                        return $args;
                    }

                    // Import the database sql file
                    if ( $orig_type == 'mysql' ) {

                        // Support MySQL
                        $command = "mysql -h localhost -u $db_name -p$db_password $db_name < $db_sql_file";
                    }else{

                        // Support PostgreSQL
                        $command = "export PGPASSWORD=\"$db_password\"; psql -h localhost -U $db_name $db_name $db_sql_file";
                    }
                    $command = $hcpp->do_action( 'quickstart_import_now_db', $command ); // Allow plugin mods
                    $result = shell_exec( $command );
                    if ( strpos( strtolower( $result ), 'error' != '' ) !== false ){
                        $this->report_status( $result_file, $result, 'error' );
                        return $args;
                    }
                }
            }

            // Update smtp.json file
            $smtp_file = $dest_folder . '/private/smtp.json';
            if ( file_exists( $smtp_file ) ) {
                try {
                    // Get the original file's permissions and ownership
                    $fileStat = stat( $smtp_file );
                    $fileMode = $fileStat['mode'];
                    $fileUid = $fileStat['uid'];
                    $fileGid = $fileStat['gid'];

                    // Update the file
                    $content = file_get_contents( $smtp_file );
                    $content = json_decode( $content, true );
                    $content['username'] = $new_domain;
                    $content['password'] = $hcpp->nodeapp->random_chars( 16 );
                    file_put_contents( $smtp_file, json_encode( $content, JSON_PRETTY_PRINT ) );

                    // Restore the original file's permissions and ownership
                    chmod( $smtp_file, $fileMode );
                    chown( $smtp_file, $fileUid );
                    chgrp( $smtp_file, $fileGid );
                }catch( Exception $e ) {
                    $this->report_status( $result_file, $e->getMessage(), 'error' );
                    return $args;
                }
            }

            // Search and replace on base files
            foreach( $manifest['ref_files'] as $file ) {
                $file = $dest_folder . '/' . $hcpp->delLeftMost( $file, '/' );
                if ( !file_exists( $file ) ) continue;
                try {
                    $searches = [$orig_domain, "/home/$orig_user"];
                    $replaces = [$new_domain, "/home/$new_user"];
                    $searches = array_merge( $searches, $orig_aliases );
                    $replaces = array_merge( $replaces, $new_aliases );
                    $this->search_replace_file( 
                        $file, 
                        $searches,
                        $replaces
                    );
                }catch( Exception $e ) {
                    $this->report_status( $result_file, $e->getMessage(), 'error' );
                    return $args;
                }
            }

            // Search and replace export advanced options
            $export_adv_options = $manifest['export_adv_options'];
            foreach( $export_adv_options as $option ) {

                // Get original value
                $value = $option['value'];
                $label = $option['label'];
                $ref_files = $option['ref_files'];
                if ( $label == '' ) continue;

                // Find new value from form
                $labelVar = 'eao_' . $this->title_to_var_name( $label );
                $new_value = '';
                if ( isset( $request[$labelVar] ) ) {
                    $new_value = $request[$labelVar];
                }

                // Get default value if multiselect
                if ( strpos( $value, "|") !== false ) {
                    $value = $hcpp->delLeftMost( $value, "|");
                    $value = $hcpp->getLeftMost( $value, "\n");
                }

                // Search and replace the value in ref. files
                foreach( $ref_files as $file ) {
                    $file = $dest_folder . '/' . $hcpp->delLeftMost( $file, '/' );
                    if ( !file_exists( $file ) ) continue;
                    if ( $value == $new_value ) continue;
                    try {
                        $this->search_replace_file( 
                            $file, 
                            [$value], 
                            [$new_value] 
                        );
                    }catch( Exception $e ) {
                        $this->report_status( $result_file, $e->getMessage(), 'error' );
                        return $args;
                    }
                }
            }
            shell_exec( 'rm -rf ' . $dest_folder . '/devstia_databases' );

            // Update the web domain backend
            $hcpp->run( "change-web-domain-backend-tpl $new_user $new_domain $backend" );

            return $args;
        }

        // Check the status of the import process
        public function quickstart_import_status( $args ) {
            $import_pid = $args[1];
            $job_id = $args[2];
            $status = shell_exec('ps -p ' . $import_pid);
            if (strpos($status, $import_pid) === false) {

                // Import is finished, check for manifest
                $manifest = '/tmp/devstia_import_' . $job_id . '/devstia_manifest.json';
                if ( file_exists( $manifest ) ) {
                    try {
                        $content = file_get_contents( $manifest );
                        $manifest = json_decode( $content, true );
                    } catch( Exception $e ) {
                        echo json_encode( [ 'status' => 'error', 'message' => 'Error parsing manifest file.' ] );
                    }
                    $message = 'Fill in options.';
                    if ( is_dir('/home/devstia') ) {
                        $message .= ' <i>Devstia Preview should use a <b>.dev.pw</b> TLD.</i>';
                    }
                    echo json_encode( [ 
                        'status' => 'finished', 
                        'message' => $message, 
                        'domain' => $manifest['domain'], 
                        'alias' => $manifest['alias'],
                        'export_adv_options' => $manifest['export_adv_options'],
                    ] );
                }else{
                    echo json_encode( [ 
                        'status' => 'error',
                        'message' => 'Import failed. Please try again.'
                    ] );
                }
            } else {
                // Import is still running
                echo json_encode( [ 'status' => 'running', 'message' => 'Please wait. Decompressing and analyzing files.' ] );
            }
            return $args;
        }

        // Import the website archive
        public function quickstart_import_file( $args ) {
            global $hcpp;
            $import_file = $args[1];
            $import_folder = $hcpp->getLeftMost( $import_file, '.' );
            if ( file_exists( $import_file ) ) {
                $command = 'unzip -o -q ' . $import_file . ' -d ' . $import_folder . ' ';
                $command .= '&& rm -rf ' . $import_file . ' ';
                $command .= '&& chown -R admin:admin ' . $import_folder;
                $command = $hcpp->do_action( 'quickstart_import_file_command', $command );
                shell_exec( $command );
            }
            return $args;
        }

        // Cancel the import process by killing the process, clean up files
        public function quickstart_import_cancel( $args ) {
            $import_pid = $args[1];
            $job_id = $args[2];
            shell_exec('kill -9 ' . $import_pid . ' ; rm -rf /tmp/devstia_import_' . $job_id . '*');
            echo json_encode( [ 'status' => 'cancelled' ] );
            return $args;
        }

        // Cancel the export process by killing the process
        public function quickstart_export_cancel( $args ) {
            $export_pid = $args[1];
            shell_exec('kill -9 ' . $export_pid);
            echo json_encode( [ 'status' => 'cancelled' ] );
            return $args;
        }

        // Check the status of the export process
        public function quickstart_export_status( $args ) {
            $export_pid = $args[1];
            $status = shell_exec('ps -p ' . $export_pid);
            if (strpos($status, $export_pid) === false) {
                // Export is finished
                echo json_encode( [ 'status' => 'finished' ] );
            } else {
                // Export is still running
                echo json_encode( [ 'status' => 'running' ] );
            }
            return $args;
        }
        
        // Get site details for the given user and domain
        public function quickstart_site_details( $args ) {
            $user = $args[1];
            $domain = $args[2];

            $details = $this->get_manifest( $user, $domain );

            // Output the found databases and migrations
            echo json_encode( $details, JSON_PRETTY_PRINT );
            return $args;
        }

        // Start the export process
        public function quickstart_export_zip( $args ) {

            // Move the manifest file to the user tmp folder
            global $hcpp;
            $json_file = $args[1];
            $content = file_get_contents( '/tmp/' . $json_file );
            $manifest = json_decode( $content, true );
            unlink( '/tmp/' . $json_file );
            $user = $manifest['user'];
            $domain = $manifest['domain'];
            $export_options = $manifest['export_options'];
            $export_adv_options = $manifest['export_adv_options'];
            $export_folder = '/home/' . $user . '/tmp/' . $hcpp->delRightMost( $json_file, '.json' );
            if ( !is_dir( $export_folder ) ) mkdir( $export_folder, true );
            file_put_contents( $export_folder . '/devstia_manifest.json', $content );
            $devstia_databases_folder = $export_folder . '/devstia_databases';

            // Dump databases to user tmp folder
            mkdir( $devstia_databases_folder, true );
            chmod( $devstia_databases_folder, 0751);
            foreach( $manifest['databases'] as $database ) {
                $db = $database['DATABASE'];
                $hcpp->run( "dump-database $user $db > \"$devstia_databases_folder/$db.sql\"" );
            }

            $public_html = $hcpp->run( "list-web-domain " . $user . " '" . $domain . "' json " );
            if ( $public_html == NULL ) return $args;
            $public_html = $public_html[$domain]['DOCUMENT_ROOT'];
            $nodeapp = str_replace( '/public_html/', '/nodeapp/', $public_html );
            $private = str_replace( '/public_html/', '/private/', $public_html );
            $cgi_bin = str_replace( '/public_html/', '/cgi-bin/', $public_html );
            $document_errors = str_replace( '/public_html/', '/document_errors/', $public_html );

            // Copy website folders to user tmp folder, accounting for export options
            $abcopy = __DIR__ . '/abcopy';
            $exvc = ';';
            if ( strpos($export_options, 'exvc') !== false ) $exvc = ' true;';
            $command = '';
            if ( strpos($export_options, 'public_html') !== false ) $command .= "$abcopy $public_html $export_folder/public_html" . $exvc;
            if ( strpos($export_options, 'nodeapp') !== false ) $command .= "$abcopy $nodeapp $export_folder/nodeapp" . $exvc;
            if ( strpos($export_options, 'private') !== false ) $command .= "$abcopy $private $export_folder/private" . $exvc;
            if ( strpos($export_options, 'cgi_bin') !== false ) $command .= "$abcopy $cgi_bin $export_folder/cgi-bin" . $exvc;
            if ( strpos($export_options, 'document_errors') !== false ) $command .= "$abcopy $document_errors $export_folder/document_errors" . $exvc;

            // Reset ownership, zip up contents, move to exports, and clean up
            $zip_file = "/home/$user/web/exports/" . $manifest['zip_file'];
            $command .= "chown -R $user:$user $export_folder && cd $export_folder ";
            $command .= "&& zip -r $export_folder.zip . && cd .. && rm -rf $export_folder ";
            $command .= "&& mkdir -p /home/$user/web/exports ";
            $command .= "&& mv $export_folder.zip $zip_file ";
            $command .= "&& chown -R $user:$user /home/$user/web/exports ";
            $command = $hcpp->do_action( 'quickstart_export_zip', $command ); // Allow plugin mods
            shell_exec( $command );
            return $args;
        }
        
        // Redirect to quickstart on login
        public function hcpp_head( $args ) {
            if ( !isset( $_GET['alt'] ) ) return $args;
            $content = $args['content'];
            if ( strpos( $content, 'LOGIN') === false ) return $args;
            $_SESSION['request_uri'] = '/list/web/?quickstart=main';
            return $args;
        }

        // Render the Quickstart body
        public function hcpp_render_body( $args ) {
            if ( !isset( $_GET['quickstart'] ) ) return $args;
            $authorized_pages = [
                'main',
                'import_options',
                'import_export',
                'import_now',
                'import',
                'export',
                'export_view',
                'export_details',
                'export_options',
                'export_now',
                'create',
                'remove_copy',
                'copy_details',
                'copy_now',
                'remove_details'
            ];

            // Sanitize the quickstart parameter, default to main
            $load = $_GET['quickstart'];
            $load = str_replace(array('/', '\\'), '', $load);
            if (empty($load) || !preg_match('/^[A-Za-z0-9_-]+$/', $load)) {
                $load = 'main';
            } 
            if ( !in_array( $load, $authorized_pages ) ) {
                $load = 'main';
            }

            // Check for valid user session or direct to login
            if(session_status() == PHP_SESSION_NONE){
                session_start();
            }
            if ( !isset( $_SESSION['user'] ) ) {
                header('Location: /login/?alt=1');
                exit;
            }

            // Load the requested page
            global $hcpp;
            ob_start();
            require( __DIR__ . '/pages/' . $load . '.php' );
            $page = ob_get_clean();
            $content = $args['content'];
            $footer = '<footer ' . $hcpp->delLeftMost( $content, '<footer ');
            $page = $hcpp->do_action('quickstart_' . $load, $page);
            $args['content'] = $page . $footer;    
            return $args;
        }

        // Render the Quickstart tab
        public function hcpp_render_panel( $args ) {
            $content = $args['content'];
            if ( !str_contains( $content, '<!-- Web tab -->' ) ) return $args;
            if ( str_contains( $content, 'class="top-bar-menu-link" href="/edit/user/?user=admin&' ) ) return $args;
            
            global $hcpp;
            $before = $hcpp->getLeftMost( $content, '<!-- Web tab -->');
            $after = '<!-- Web tab -->' . $hcpp->delLeftMost( $content, '<!-- Web tab -->');
            $active = isset($_GET['quickstart']) ? ' active' : '';
            if ( $active != '' ) {
                $after = str_replace( 'class="main-menu-item-link active"', 'class="main-menu-item-link"', $after);
            }
            $qs_tab = '<!-- Quickstart tab -->
            <li class="main-menu-item">
                <a class="main-menu-item-link' . $active . '" href="/list/web/?quickstart=main" title="Easy-to-use guide">
                    <p class="main-menu-item-label">QUICKSTART<i class="fas fa-flag-checkered"></i></p>
                    <ul class="main-menu-stats">
                        <li> easy-to-use guide</li>
                    </ul>
                </a>
            </li>';

            // Default to quickstart if logo is clicked
            $before = str_replace( '<a href="/" class="top-bar-logo"', '<a href="https://devstia.com/preview" target="_blank" class="top-bar-logo"', $before);
            
            // Customize help link
            $before = str_replace( 'href="https://hestiacp.com/docs/"', 'href="https://devstia.com/preview/support/"', $before );

            $content = $before . $qs_tab . $after;
            $args['content'] = $content;
            return $args;
        }

        /**
         * Get the site details for the given user's website domain. Highly optimized for speed,
         * scan revelent files for db credentials, and migration details (domain, aliases, 
         * user path) and return details as an associative array.
         * 
         * @param string $user The username of the user.
         * @param string $domain The domain of the website.
         * @return array An associative array of the site details.
         */
        public function get_manifest( $user, $domain ) {

            // Get a list of databases for the given user
            global $hcpp;
            $data = $hcpp->run( "list-databases " . $user . " json" );
            $databases = [];
            foreach ($data as $key => $value) {
                array_push( $databases, $value );
            }

            // Get a list of folders to scan for credentials
            $web_domain = $hcpp->run( "list-web-domain " . $user . " '" . $domain . "' json " );
            if ( $web_domain == NULL ) return [];
            $public_html = $web_domain[$domain]['DOCUMENT_ROOT'];
            $nodeapp = str_replace( '/public_html/', '/nodeapp/', $public_html );

            // Gather web domain items for migration; mentions of domain, aliases, or user folder
            $aliases = $web_domain[$domain]['ALIAS'];
            $aliases = explode( ',', $aliases );
            $migrate_strings = [
                "/home/$user", 
                $domain
            ];
            $migrate_ref_files = [];
            if ( !empty( $aliases ) && $aliases[0] != '' ) {
                foreach( $aliases as $alias ) {
                    $migrate_strings[] = $alias;
                }
            }

            // Omit folders, and file extensions for scan
            $omit_folders = array( 'src', 'core', 'includes', 'public', 'current', 'content', 'core', 'uploads', 
                'logs', '.git', '.svn', '.hg', 'versions', 'node_modules', 'wp-content', 'wp-includes',
                'wp-admin', 'vendor', 'mw-config', 'extensions', 'maintenance', 'i18n', 'skins' );
            $match_extensions = array( 'php', 'ts', 'js', 'json', 'conf', 'config', 'jsx', 'ini', 'sh', 'xml', 'inc',
                'cfg', 'yml', 'yaml', 'py', 'rb', 'env' );

            // Get list of files to check from public_html folder
            $files = [];
            if ( is_dir( $public_html ) ) {

                // First get a list of all subfolders of public_html
                $omit_folders = array_map(function($folder) {
                    return '/' . $folder . '/';
                }, $omit_folders);
                $omit_folders_pattern = implode('|', array_map('preg_quote', $omit_folders));
                $command = "find $public_html -type d | egrep -v '$omit_folders_pattern'";
                $subfolders = array_filter(explode("\n", shell_exec($command)));

                // Given a list of subfolders, get a list of all files that have the match extensions
                $match_extensions_pattern = implode('|', array_map('preg_quote', $match_extensions));
                foreach( $subfolders as $folder) {
                    $command = "find $folder -maxdepth 1 -type f | egrep '$match_extensions_pattern'";    
                    $f = array_filter(explode("\n", shell_exec($command)));
                    $files = array_merge( $files, $f );
                }

            }

            // Get list of files to check from nodeapp folder
            if ( is_dir( $nodeapp ) ) {

                // First get a list of all subfolders of nodeapp
                $omit_folders_pattern = implode('|', array_map('preg_quote', $omit_folders));
                $command = "find $nodeapp -type d | egrep -v '$omit_folders_pattern'";
                $subfolders = array_filter(explode("\n", shell_exec($command)));

                // Given a list of subfolders, get a list of all files that have the match extensions
                $match_extensions_pattern = implode('|', array_map('preg_quote', $match_extensions));
                foreach( $subfolders as $folder) {
                    $command = "find $folder -maxdepth 1 -type f | egrep '$match_extensions_pattern'";    
                    $f = array_filter(explode("\n", shell_exec($command)));
                    $files = array_merge( $files, $f );
                }
            }
            
            // Check the given files 
            foreach( $files as $file ) {
                if ( strpos( $file, 'devstia_manifest.json' ) !== false ) continue;
                $content = file_get_contents( $file );
                $index = 0;

                // Check for mentions of database, domain, aliases, 
                foreach( $databases as $database ) {
                    if ( !isset( $database['ref_files'] ) ) $database['ref_files'] = [];
                    if ( strpos( $content, $database['DATABASE'] ) !== false ) {
                        $database['ref_files'][] = $file;// $hcpp->delLeftMost( $file, $domain );
                    }
                    $databases[$index] = $database;
                    $index++;
                }

                // Check for any of the migrate strings
                foreach( $migrate_strings as $m ) {
                    if ( !in_array( $file, $migrate_ref_files ) ) {
                        if ( strpos( $content, $m ) !== false ) {
                            $migrate_ref_files[] = $file;
                        }else{
                            // Check escape encoded version too
                            if ( strpos( $content, addcslashes( $m, "\/\n\r\0" ) ) !== false ) {
                                $migrate_ref_files[] = $file;
                            }
                        }   
                    }
                }
            }

            // Analyze dbs that have assoc. files, and extract the password for each database
            $found_dbs = [];
            foreach( $databases as $database ) {
                if ( !isset( $database['ref_files'] ) || count( $database['ref_files'] ) == 0 ) continue;

                // Search for the password in the first reference file
                $database['DBPASSWORD'] = "";
                foreach( $database['ref_files'] as $file) {
                    $content = file_get_contents( $file );
                    $content = $hcpp->delLeftMost( $content, $database['DATABASE'] );
                    $pwkeys = ["PASSWORD'", 'PASSWORD"', 'password"', "password'", 'password =', 'password='];
                    $fkey = "";
                    foreach( $pwkeys as $pwkey ) {
                        $keypos = strpos( $content, $pwkey );
                        if ( $keypos === false ) continue;
                        if ( $fkey == "" ) $fkey = $pwkey;
                        if ( $keypos < strpos( $content, $fkey ) ) $fkey = $pwkey;
                    }
                    if ( $fkey == "" ) continue;
                }
                if ( $fkey != "" ) {
                    $content = $hcpp->delLeftMost( $content, $fkey );
                    
                    // Find the first instance of a single quote or double quote, whichever comes first
                    $singleQuotePos = strpos($content, "'");
                    $doubleQuotePos = strpos($content, '"');
                    if ($singleQuotePos === false && $doubleQuotePos === false) {
                        // No quotes found
                        $qchar = '';
                    } elseif ($singleQuotePos === false) {
                        // Only double quote found
                        $qchar = '"';
                    } elseif ($doubleQuotePos === false) {
                        // Only single quote found
                        $qchar = "'";
                    } else {
                        // Both quotes found, get the first one
                        $qchar = $singleQuotePos < $doubleQuotePos ? "'" : '"';
                    }
                    if ( $qchar == '' ) continue;

                    // Parse out the password enclosed in the quotes $qchar
                    $content = $hcpp->delLeftMost( $content, $qchar );
                    $password = $hcpp->getLeftMost( $content, $qchar );
                    $database['DBPASSWORD'] = $password;
                    $found_dbs[] = $database;
                    continue;
                }
            }

            // Append migrate files to the found_dbs array
            $found_dbs[] = [ 
                'domain' => $domain,
                'aliases' => $aliases,
                'ref_files' => $migrate_ref_files 
            ];

            // Make ref_files relative paths
            foreach( $found_dbs as $key => $db ) {
                foreach( $db['ref_files'] as $key2 => $file ) {
                    $found_dbs[$key]['ref_files'][$key2] = "." . $hcpp->delLeftMost( $file, $domain );
                }
            }

            // Output the found databases and migrations
            return $found_dbs;
        }

        /**
         * Search and replace the given string in the given source text file, with special support
         * for PHP serialized strings in MySQL's "quickdump" file format, and the search for escaped
         * characters in the strings.
         * 
         * @param string $file The path and filename to the file to modify.
         * @param string|string[] $search The string or array of strings to search for.
         * @param string|string[] $replace The string or array of strings to replace with.
         */
        // Search and replace the given string in the given SQL file, assuming it's a quickdump file
        public function search_replace_file( $file, $search, $replace ) {

            // Check parameters
            if ( !file_exists( $file ) ) {
                throw new Exception( "File '$file' does not exist." );
            }
            if ( !is_string( $search ) && !is_array( $search ) ) {
                throw new Exception( "Parameter 'search' must be a string or array." );
            }
            if ( !is_string( $replace ) && !is_array( $replace ) ) {
                throw new Exception( "Parameter 'replace' must be a string or array." );
            }
            if ( is_string( $search ) ) {
                $search = [ $search ];
            }
            if ( is_string( $replace ) ) {
                $replace = [ $replace ];
            }
            if ( count( $search ) != count( $replace ) ) {
                throw new Exception( "Parameters 'search' and 'replace' must have the same number of elements." );
            }

            // Duplicate search and replace strings with escaped versions if necessary
            $searchEscaped = [];
            $replaceEscaped = [];
            for ($i = 0; $i < count($search); $i++) {
                $searchE = addcslashes($search[$i], "\/\n\r\0");
                
                // Check if already in array
                if ( in_array( $searchE, $search ) ) continue;
                $searchEscaped[] = $searchE;
                $replaceEscaped[] = addcslashes($replace[$i], "\/\n\r\0");
            }
            $search = array_merge( $search, $searchEscaped );
            $replace = array_merge( $replace, $replaceEscaped );

            // Load the file and replace the strings
            $handle = fopen( $file, 'r' );
            $tempFile = $file . '.tmp';
            $writeStream = fopen( $tempFile, 'w' );
            $regex1 = "/('.*?'|[^',\s]+)(?=\s*,|\s*;|\s*$)/";
            global $regex2;
            $regex2 = "/s:(\d+):\"(.*?)\";/ms";
            while ( ( $line = fgets( $handle ) ) !== false ) {
                $origLine = $line;
                $line = trim( $line );
                $bModifed = false;
                for ( $i = 0; $i < count( $search ); $i++ ) {
                    $searchString = $search[$i];
                    $replaceString = $replace[$i];
                    if ( strpos( $line, $searchString ) !== false && $searchString != $replaceString ) {

                        // Sense Quickdump format
                        if (strpos($line, "(") === 0 && (substr($line, -2) === ")," || substr($line, -2) === ");")) {
                            $startLine = substr( $line, 0, 1 );
                            $endLine = substr( $line, -2 );
                            $line = substr( $line, 1, -2 );
                            $line = str_replace("\\0", "~0Placeholder", $line );
                            $matches = [];
                            preg_match_all( $regex1, $line, $matches );
                            $items = $matches[0];
                            $line = implode( '', [$startLine, implode( ",", array_map( function ( $item ) use ( $searchString, $replaceString ) {
                                if (strpos( $item, "'" ) === 0 && strrpos( $item, "'" ) === strlen( $item ) - 1 ) {
                                    $item = substr( $item, 1, -1 );
                                    $item = str_replace( $searchString, $replaceString, $item );

                                    // Sense serialized strings
                                    if ( $this->is_serialized( $item ) ) {

                                        // Recalculate the length of the serialized strings
                                        $item = json_decode(json_encode( $item ) );
                                        $item = str_replace( "\\", "", $item );
                                        $item = str_replace( "~0Placeholder", "\0", $item );
                                        global $regex2;
                                        $item = preg_replace_callback( $regex2, function ( $matches ) {
                                            return 's:' . strlen( $matches[2] ) . ':"' . $matches[2] . '";';
                                        }, $item);
                                        $item = addslashes( $item );
                                    }else{
                                        $item = str_replace( "\0", "~0Placeholder", $item );
                                    }
                                    return implode( '', ["'" , $item , "'"] );
                                } else if ( $item === 'null' ) {
                                    return null;
                                } else if ( is_numeric( $item ) ) {
                                    return (float)$item;
                                } else {
                                    return $item;
                                }
                            }, $items ) ), $endLine] );
                        }else{
                            $line = str_replace( $searchString, $replaceString, $line );
                        }
                        $bModifed = true;
                    }
                }
                if (false === $bModifed) {
                    $line = $origLine;
                }

                // Ensure the line ends with a newline
                if ( substr( $line, -1 ) != "\n" ) {
                    $line .= "\n";
                }
                fwrite( $writeStream, $line );
            }
            fclose( $handle );
            fclose( $writeStream );

            // Check for multi-line block in search and replace them in
            // the temp file as a whole because line-by-line won't work.
            for ( $i = 0; $i < count( $search ); $i++ ) {
                $searchString = $search[$i];
                $replaceString = $replace[$i];
                if ( strpos( $searchString, "\n" ) !== false ) {
                    $content = file_get_contents( $tempFile );
                    $content = str_replace( $searchString, $replaceString, $content );
                    file_put_contents( $tempFile, $content );
                }
            }

            // Get the original file's permissions and ownership
            $fileStat = stat( $file );
            $fileMode = $fileStat['mode'];
            $fileUid = $fileStat['uid'];
            $fileGid = $fileStat['gid'];

            // Replace the original file with the temp file
            rename( $tempFile, $file );

            // Restore the original file's permissions and ownership
            chmod( $file, $fileMode );
            chown( $file, $fileUid );
            chgrp( $file, $fileGid );
        }

        // Check if a string is serialized
        public function is_serialized( $data, $strict = true ) {
            if (strlen( $data) < 4 ) {
                return false;
            }
            if ( $data[1] !== ':' ) {
                return false;
            }
            if ( $data === 'N;' ) {
                return true;
            }
            if ( $strict) {
                $lastc = $data[strlen( $data) - 1];
                if ( $lastc !== ';' && $lastc !== '}' ) {
                    return false;
                }
            } else {
                $semicolon = strpos( $data, ';' );
                $brace = strpos( $data, '}' );
                // Either ; or } must exist.
                if ( $semicolon === false && $brace === false ) {
                    return false;
                }
                // But neither must be in the first X characters.
                if ( $semicolon !== false && $semicolon < 3 ) {
                    return false;
                }
                if ( $brace !== false && $brace < 4 ) {
                    return false;
                }
            }
            $token = $data[0];
            switch ( $token ) {
                case 's':
                    if ( $strict ) {
                        if ( $data[strlen( $data) - 2] !== '"' ) {
                            return false;
                        }
                    } else if (!strpos( $data, '"' ) ) {
                        return false;
                    }
                    // Or else fall through.
                case 'a':
                case 'O':
                case 'E':
                    return (bool)preg_match( "/^" . $token . ":[0-9]+:/", $data );
                case 'b':
                case 'i':
                case 'd':
                    $end = $strict ? '$' : '';
                    return (bool)preg_match( "/^" . $token . ":[0-9.E+-]+;" . $end . "/", $data );
            }
            return false;
        }

        // Convert a title to a valid variable name
        public function title_to_var_name( $str ) {
            $str = strtolower($str); // Convert all characters to lowercase
            $str = preg_replace('/[^a-z0-9\s]/', '', $str); // Remove all non-alphanumeric characters except spaces
            $str = preg_replace('/\s+/', '_', $str); // Replace one or more spaces with underscores
            $str = preg_replace_callback('/_([a-z])/', function ($match) { return strtoupper($match[1]); }, $str); // Convert underscores to camelCase
            return $str;
        }

        /**
         * Get a quickstart process status by unique key.
         * @param string $key The unique key for the process.
         * @return array An associative array of the process status.
         */
        public function get_status( $unique_key ) {
            $pid_file = '/tmp/devstia_' . $unique_key . '.pid';
            $result_file = '/tmp/devstia_' . $unique_key . '.result';
            $result = [];
            if ( file_exists( $pid_file ) ) {
                $pid = file_get_contents( $pid_file );
                $status = shell_exec('ps -p ' . $pid);
                if (strpos($status, $pid) === false) {
                    $result['status'] = 'finished';
                } else {
                    $result['status'] = 'running';
                }
            }
            $result['message'] = '';
            if ( file_exists( $result_file ) ) {
                $content = file_get_contents( $result_file );
                $result = json_decode( $content, true );
            }
            if ( $result['status'] == 'finished' ) {
                unlink( $pid_file );
                unlink( $result_file );
            }
            return $result;
        }

        /**
         * Report a quickstart process status by unique key.
         * @param string $key The unique key for the process.
         * @param string $message The message to report.
         * @param string $status The status to report.
         */
        public function report_status( $unique_key, $message, $status = 'running' ) {
            $result_file = '/tmp/devstia_' . $unique_key . '.result';
            $result = json_encode( [ 'status' => $status, 'message' => $message ] );
            unlink( $result_file );
            file_put_contents( $result_file, $result );
            chown( $result_file, 'admin' );
            chgrp( $result_file, 'admin' );
        }

    }
    new Quickstart();
}
