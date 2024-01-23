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
            $hcpp->add_action( 'hcpp_head', [ $this, 'hcpp_head' ] );
            $hcpp->add_action( 'hcpp_invoke_plugin', [ $this, 'hcpp_invoke_plugin' ] );
            $hcpp->add_action( 'hcpp_rebooted', [ $this, 'hcpp_rebooted' ] );
            $hcpp->add_action( 'hcpp_render_body', [ $this, 'hcpp_render_body' ] );
            $hcpp->add_action( 'hcpp_render_panel', [ $this, 'hcpp_render_panel' ] );
        }

        /** 
         * Create a unique job id, all jobs are prefixed with devstia_job_id for future listing.
         * @return string The unique job id.
         */
        public function create_job() {
            global $hcpp;
            $job_id = $hcpp->nodeapp->random_chars( 16 );
            if ( !isset( $_SESSION['devstia_jobs']) ) $_SESSION['devstia_jobs'] = [];
            $_SESSION['devstia_jobs'][$job_id] = [];
            return $job_id; 
        }

        /**
         * Check if the given job id is valid.
         * @param string $job_id The unique job id.
         */
        public function is_job_valid( $job_id ) {
            if ( !isset( $_SESSION['devstia_jobs'][$job_id] ) ) return false;
            return true;
        }

        /**
         * Get the given job data with a reference key.
         * @param string $job_id The unique job id.
         * @param string $key The key of the data to get.
         * @return mixed The data value.
         */
        public function get_job_data( $job_id, $key ) {
            if ( !isset( $_SESSION['devstia_jobs'][$job_id] ) ) return false;
            if ( !isset( $_SESSION['devstia_jobs'][$job_id][$key] ) ) return false;
            return $_SESSION['devstia_jobs'][$job_id][$key];
        }

        /**
         * Set the given job data with a reference key.
         * @param string $job_id The unique job id.
         * @param string $key The key of the data to set.
         * @param mixed $value The value of the data to set.
         * @return bool True if successful, false otherwise.
         */
        public function set_job_data( $job_id, $key, $value ) {
            if ( !isset( $_SESSION['devstia_jobs'][$job_id] ) ) return false;
            $_SESSION['devstia_jobs'][$job_id][$key] = $value;
            return true;
        }

        /**
         * Transfer the given job data to a file with admin privileges; allowing a priviledged
         * process to get otherwise inaccessible admin session data.
         * @param string $job_id The unique job id.
         * @param string $key The key of the data to transfer.
         * @return bool True if successful, false otherwise.
         */
        public function xfer_job_data( $job_id, $key ) {
            if ( !isset( $_SESSION['devstia_jobs'][$job_id] ) ) return false;
            if ( !isset( $_SESSION['devstia_jobs'][$job_id][$key] ) ) return false;
            $value = json_encode( $_SESSION['devstia_jobs'][$job_id][$key], JSON_PRETTY_PRINT );
            file_put_contents( "/tmp/devstia_$job_id-$key.json", $value );
            chown( "/tmp/devstia_$job_id-$key.json", 'admin' );
            chgrp( "/tmp/devstia_$job_id-$key.json", 'admin' );
            return true;
        }

        /**
         * Pickup the given job data from a file and remove it; this allows a priviledged
         * process to get otherwise inaccessible admin session data.
         * @param string $job_id The unique job id.
         * @param string $key The key of the data to pickup.
         * @return mixed The data value.
         */
        public function pickup_job_data( $job_id, $key ) {
            if ( file_exists( "/tmp/devstia_$job_id-$key.json" ) ) {
                try {
                    $value = file_get_contents( "/tmp/devstia_$job_id-$key.json" );
                    $value = json_decode( $value, true );
                    unlink( "/tmp/devstia_$job_id-$key.json" );
                    return $value;
                } catch (Exception $e) {
                    return false;
                }
            }else{
                return false;
            }
        }

        /**
         * Export the website to a zip file with the given manifest.
         * @param array $manifest The manifest of the website to export.
         */
        public function export_zip( $manifest ) {
            
            // // Create our priviledged command to export the website to a zip file
            // $this->create_invoke_plugin_fn( 'export_zip', function( $manifest ) {
            //
            // } );



            global $hcpp;
            $this->set_job_data( $manifest['job_id'], 'manifest', $manifest );
            $this->xfer_job_data( $manifest['job_id'], 'manifest' );

            // Start the export process asynchonously and get the process id
            //$export_pid = trim( shell_exec(HESTIA_CMD . "v-invoke-plugin quickstart_export_zip " . $manifest['job_id'] . " > /dev/null 2>/dev/null & echo $!") );

            // Store the process id data for the job, to be used for status checks
            //$this->set_job_data( $manifest['job_id'], 'export_pid', $export_pid );
        } 

        /**
         * Get the site manifest for the given user's website domain. Highly optimized for speed,
         * scan revelent files for db credentials, and migration details (domain, aliases, 
         * user path) and return site and database details as an associative array.
         * 
         * @param string $user The username of the user.
         * @param string $domain The domain of the website.
         * @return array An associative array of the site details.
         */
        public function get_manifest( $user, $domain ) {
            global $hcpp;
            return $hcpp->run( "invoke-plugin quickstart_get_manifest " . $user . " " . $domain . " json" );
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
         * Redirect to quickstart on login.
         */
        public function hcpp_head( $args ) {
            if ( !isset( $_GET['alt'] ) ) return $args;
            $content = $args['content'];
            if ( strpos( $content, 'LOGIN') === false ) return $args;
            $_SESSION['request_uri'] = '/list/web/?quickstart=main';
            return $args;
        }

        /**
         * Run trusted elevated commands from the v-invoke-plugin command.
         */
        public function hcpp_invoke_plugin( $args ) {
            $trusted = [
                'quickstart_get_manifest',
                'quickstart_export_zip'
            ];
            if ( in_array( $args[0], $trusted ) ) {
                return call_user_func_array([$this, $args[0]], [$args]);
            }else{
                return $args;
            }
        }

        /**
         * Clean up all the devstia_* files in /tmp and /home/user/tmp folders
         * on reboot.
         */
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

        /**
         * Render the Quickstart pages in the body.
         */
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

        /**
         * Render the quickstart panel tab.
         */
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
         * Our trusted elevated command to export a website to a zip file; used by $this->export_zip().
         */
        public function quickstart_export_zip( $args ) {
            $job_id = $args[1];
            $manifest = $this->pickup_job_data( $job_id, 'manifest' );
            if ( $manifest == false ) return $args;
            
            global $hcpp;
            $user = $manifest['user'];
            $domain = $manifest['domain'];
            $export_options = $manifest['export_options'];
            $export_adv_options = $manifest['export_adv_options'];
            $export_folder = '/home/' . $user . '/tmp/devstia_export_' . $job_id;
            if ( !is_dir( $export_folder ) ) mkdir( $export_folder, true );
            file_put_contents( $export_folder . '/devstia_manifest.json', json_encode( $manifest, JSON_PRETTY_PRINT) );
            $devstia_databases_folder = $export_folder . '/devstia_databases';

            // Dump databases to user tmp folder
            mkdir( $devstia_databases_folder, true );
            chmod( $devstia_databases_folder, 0751);
            foreach( $manifest['databases'] as $database ) {
                 $db = $database['DATABASE'];
                 $hcpp->run( "dump-database $user $db > \"$devstia_databases_folder/$db.sql\"" );
            }
            $public_html = "/home/$user/web/$domain/public_html";
            $nodeapp = "/home/$user/web/$domain/nodeapp";
            $private = "/home/$user/web/$domain/private";
            $cgi_bin = "/home/$user/web/cgi-bin";
            $document_errors = "/home/$user/web/$domain/document_errors";

            // Copy website folders to user tmp folder, accounting for export options
            $abcopy = __DIR__ . '/abcopy';
            $exvc = ';';
            if ( strpos($export_options, 'exvc') !== false ) $exvc = ' true;';
            $command = '';
            if ( strpos($export_options, 'public_html') !== false && is_dir( $public_html) ) {
                $command .= "$abcopy $public_html $export_folder/public_html" . $exvc;
            }
            if ( strpos($export_options, 'nodeapp') !== false &&  is_dir( $nodeapp ) ) {
                $command .= "$abcopy $nodeapp $export_folder/nodeapp" . $exvc;
            } 
            if ( strpos($export_options, 'private') !== false && is_dir( $private ) ) {
                $command .= "$abcopy $private $export_folder/private" . $exvc;
            }
            if ( strpos($export_options, 'cgi_bin') !== false && is_dir( $cgi_bin ) ) {
                $command .= "$abcopy $cgi_bin $export_folder/cgi-bin" . $exvc;
            }
            if ( strpos($export_options, 'document_errors') !== false && is_dir( $document_errors ) ) {
                $command .= "$abcopy $document_errors $export_folder/document_errors" . $exvc;
            }

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

        /**
         * Our trusted elevated command to get site manifest; used by $this->get_manifest().
         * @param array $args The arguments passed to the command.
         */
        public function quickstart_get_manifest( $args ) {
            $user = $args[1];
            $domain = $args[2];

            // Get a list of databases for the given user
            global $hcpp;
            $data = $hcpp->run( "list-databases " . $user . " json" );
            $databases = [];
            foreach ($data as $key => $value) {
                array_push( $databases, $value );
            }

            // Get a list of folders to scan for credentials
            $web_domain = $hcpp->run( "list-web-domain " . $user . " '" . $domain . "' json " );
            if ( $web_domain == NULL ) {
                echo json_encode( [ 'error' => 'Domain not found' ] );
                exit();
            }
            $public_html = $web_domain[$domain]['DOCUMENT_ROOT'];
            $nodeapp = str_replace( '/public_html/', '/nodeapp/', $public_html );

            // Gather web domain items for migration; mentions of domain, aliases, or user folder
            $aliases = $web_domain[$domain]['ALIAS'];
            $backend = $web_domain[$domain]['BACKEND'];
            $proxy = $web_domain[$domain]['PROXY'];
            $proxy_ext = $web_domain[$domain]['PROXY_EXT'];
            $template = $web_domain[$domain]['TPL'];
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

                // Check for mentions of database, domain, aliases
                foreach( $databases as $database ) {
                    if ( !isset( $database['ref_files'] ) ) $database['ref_files'] = [];
                    if ( strpos( $content, $database['DATABASE'] ) !== false ) {
                        $database['ref_files'][] = $file;
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
            $db_details = [];
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
                    $db_details[] = $database;
                    continue;
                }
            }

            // Make ref_files relative paths
            foreach( $db_details as $key => $db ) {
                foreach( $db['ref_files'] as $key2 => $file ) {
                    $db_details[$key]['ref_files'][$key2] = "." . $hcpp->delLeftMost( $file, $domain );
                }
            }
            foreach( $migrate_ref_files as $key => $file ) {
                $migrate_ref_files[$key] = "." . $hcpp->delLeftMost( $file, $domain );
            }

            // Output the site and database details
            $site_details = [ 
                'domain' => $domain,
                'aliases' => $aliases,
                'backend' => $backend,
                'proxy' => $proxy,
                'proxy_ext' => $proxy_ext,
                'template' => $template,
                'ref_files' => $migrate_ref_files,
                'databases' => $db_details
            ];
            echo json_encode( $site_details );
            return $args;
        }

        /**
         * Report a quickstart process status by unique key.
         * @param string $key The unique key for the process.
         * @param string $message The message to report.
         * @param string $status The status to report.
         */
        public function report_status( $job_id, $message, $status = 'running' ) {
            $result_file = '/tmp/devstia_' . $job_id . '.result';
            $result = json_encode( [ 'status' => $status, 'message' => $message ] );
            unlink( $result_file );
            file_put_contents( $result_file, $result );
            chown( $result_file, 'admin' );
            chgrp( $result_file, 'admin' );
        }
    }
    new Quickstart();
}
