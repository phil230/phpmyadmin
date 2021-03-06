<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 *
 */
require_once './libraries/file_listing.php';
require_once './libraries/plugin_interface.lib.php';
require_once './libraries/display_import_ajax.lib.php';

/* Scan for plugins */
$import_list = PMA_getPlugins('./libraries/import/', $import_type);

/* Fail if we didn't find any plugin */
if (empty($import_list)) {
    PMA_Message::error(__('Could not load import plugins, please check your installation!'))->display();
    include './libraries/footer.inc.php';
}
?>

<iframe id="import_upload_iframe" name="import_upload_iframe" width="1" height="1" style="display: none;"></iframe>
<div id="import_form_status" style="display: none;"></div>
<div id="importmain">
    <img src="<?php echo $GLOBALS['pmaThemeImage'];?>ajax_clock_small.gif" width="16" height="16" alt="ajax clock" style="display: none;" />
    <script type="text/javascript">
        //<![CDATA[
        $(document).ready( function() {
            // add event when user click on "Go" button
            $('#buttonGo').bind('click', function() {
                $('#upload_form_form').css("display", "none"); // hide form
                $('#upload_form_status').css("display", "inline"); // show progress bar
                $('#upload_form_status_info').css("display", "inline"); // - || -
<?php
if ($_SESSION[$SESSION_KEY]["handler"]!="noplugin") {
    ?>
                var finished = false;
                var percent  = 0.0;
                var total    = 0;
                var complete = 0;
                var original_title = parent && parent.document ? parent.document.title : false;
                var import_start;

                var perform_upload = function () {
                    new $.getJSON(
                    'import_status.php?id=<?php echo $upload_id ; ?>&<?php echo PMA_generate_common_url(); ?>',
                    {},
                    function(response) {
                        finished = response.finished;
                        percent = response.percent;
                        total = response.total;
                        complete = response.complete;

                        if (total==0 && complete==0 && percent==0) {
                            $('#upload_form_status_info').html('<img src="<?php echo $GLOBALS['pmaThemeImage'];?>ajax_clock_small.gif" width="16" height="16" alt="ajax clock" /> <?php echo PMA_jsFormat(__('The file being uploaded is probably larger than the maximum allowed size or this is a known bug in webkit based (Safari, Google Chrome, Arora etc.) browsers.'), false); ?>');
                            $('#upload_form_status').css("display", "none");
                        } else {
                            var now = new Date();
                            now = Date.UTC(
                                now.getFullYear(), now.getMonth(), now.getDate(),
                                now.getHours(), now.getMinutes(), now.getSeconds())
                                + now.getMilliseconds() - 1000;
                            var statustext = $.sprintf('<?php echo PMA_escapeJsString(__('%s of %s')); ?>',
                                formatBytes(complete, 1, PMA_messages.strDecimalSeparator),
                                formatBytes(total, 1, PMA_messages.strDecimalSeparator)
                            );

                            if ($('#importmain').is(':visible')) {
                                // show progress UI
                                $('#importmain').hide();
                                $('#import_form_status')
                                    .html('<div class="upload_progress"><div class="upload_progress_bar_outer"><div class="percentage"></div><div id="status" class="upload_progress_bar_inner"><div class="percentage"></div></div></div><div><img src="<?php echo $GLOBALS['pmaThemeImage'];?>ajax_clock_small.gif" width="16" height="16" alt="ajax clock" /> <?php echo PMA_jsFormat(__('Uploading your import file...'), false); ?></div><div id="statustext"></div></div>')
                                    .show();
                                import_start = now;
                            }
                            else if (percent > 9 || complete > 2000000) {
                                // calculate estimated time
                                var used_time = now - import_start;
                                var seconds = parseInt(((total - complete) / complete) * used_time / 1000);
                                var speed = '<?php echo PMA_jsFormat(__('%s/sec.'), false); ?>'
                                    .replace('%s', formatBytes(complete / used_time * 1000, 1, PMA_messages.strDecimalSeparator));

                                var minutes = parseInt(seconds / 60);
                                seconds %= 60;
                                var estimated_time;
                                if (minutes > 0) {
                                    estimated_time = '<?php echo PMA_jsFormat(__('About %MIN min. %SEC sec. remaining.'), false); ?>'
                                        .replace('%MIN', minutes).replace('%SEC', seconds);
                                }
                                else {
                                    estimated_time = '<?php echo PMA_jsFormat(__('About %SEC sec. remaining.'), false); ?>'
                                        .replace('%SEC', seconds);
                                }

                                statustext += '<br />' + speed + '<br /><br />' + estimated_time;
                            }

                            var percent_str = Math.round(percent) + '%';
                            $('#status').animate({width: percent_str}, 150);
                            $('.percentage').text(percent_str);

                            // show percent in window title
                            if (original_title !== false) {
                                parent.document.title = percent_str + ' - ' + original_title;
                            }
                            else {
                                document.title = percent_str + ' - ' + original_title;
                            }
                            $('#statustext').html(statustext);
                        } // else

                        if (finished == true) {
                            if (original_title !== false) {
                                parent.document.title = original_title;
                            }
                            else {
                                document.title = original_title;
                            }
                            $('#importmain').hide();
                            $('#import_form_status')
                                .html('<img src="<?php echo $GLOBALS['pmaThemeImage'];?>ajax_clock_small.gif" width="16" height="16" alt="ajax clock" /> <?php echo PMA_jsFormat(__('The file is being processed, please be patient.'), false); ?> ')
                                .show();
                            $('#import_form_status').load('import_status.php?message=true&<?php echo PMA_generate_common_url(); ?>'); // loads the message, either success or mysql error
                            <?php
                            // reload the left sidebar when the import is finished
                            $GLOBALS['reload'] = true;
                            PMA_reloadNavigation(true);
                            ?>

                        } // if finished
                        else {
                            setTimeout(perform_upload, 1000);
                        }
                    });
                }
                setTimeout(perform_upload, 1000);

    <?php
} else { // no plugin available
    ?>
                        $('#upload_form_status_info').html('<img src="<?php echo $GLOBALS['pmaThemeImage'];?>ajax_clock_small.gif" width="16" height="16" alt="ajax clock" /> <?php echo PMA_jsFormat(__('Please be patient, the file is being uploaded. Details about the upload are not available.'), false) . PMA_showDocu('faq2_9'); ?>');
                        $('#upload_form_status').css("display", "none");
    <?php
} // else
?>
                    }); // onclick
                }); // domready
                //]]>
    </script>
    <form action="import.php" method="post" enctype="multipart/form-data" name="import"<?php if ($_SESSION[$SESSION_KEY]["handler"]!="noplugin") echo ' target="import_upload_iframe"'; ?>>
    <input type="hidden" name="<?php echo $ID_KEY; ?>" value="<?php echo $upload_id ; ?>" />
    <?php
    if ($import_type == 'server') {
        echo PMA_generate_common_hidden_inputs('', '', 1);
    } elseif ($import_type == 'database') {
        echo PMA_generate_common_hidden_inputs($db, '', 1);
    } else {
        echo PMA_generate_common_hidden_inputs($db, $table, 1);
    }
    echo '    <input type="hidden" name="import_type" value="' . $import_type . '" />'."\n";
    ?>

    <div class="exportoptions" id="header">
        <h2>
            <?php echo PMA_getImage('b_import.png', __('Import')); ?>
            <?php
            if ($import_type == 'server') {
                echo __('Importing into the current server');
            } elseif ($import_type == 'database') {
                printf(__('Importing into the database "%s"'), htmlspecialchars($db));
            } else {
                printf(__('Importing into the table "%s"'), htmlspecialchars($table));
            }?>
        </h2>
    </div>
	<div class="importoptions" id="tableselect" style="display:
		<?php 
			if (isset($_SESSION['t_select']) && $_SESSION['t_select'])
			{
				echo "block";
			}		
			else
			{
				echo "none";
			}
		?>
		">
		<h3><?php echo __('Table Selection:');?></h3>
			<?php
			//Create Table + Input lists
				if (isset($_SESSION['t_names'])) { 
					$names = $_SESSION['t_names'];
					$inserts = $_SESSION['t_insert_data'];
					for ($i=0;$i<count($names);$i++) {
						echo "<p style='margin: 15px 0px'><input type='checkbox' checked='checked' name='table_select[$i]' /><img id='expand_$i' class='expand' style='cursor:pointer' src='themes/pmahomme/img/more.png' alt='+' /><label for='table_select[$i]'><strong>$names[$i]</strong></label></p>";
						if (isset($inserts[$i])) {
						echo "<table id='inserts_$i' style='margin: 0px 25px;display:none'>";
							foreach ($inserts[$i] as $row) {
								echo "<tr>";
								foreach ($row as $cell) {
									echo "<td style='border:solid black 1px'>";
									if ($row == $inserts[$i][0]) {
										echo "<em>$cell</em></td>";
									} else {
										echo "$cell</td>";	
									}
								}
								echo "</tr>";
							}
							echo "</table>";
						} else {
							echo "<p id='inserts_$i' style='display:none;margin: 0px 25px'><em>No values to insert.</em></p>";
						}
					}
				}
			?>
	</div>
    <div class="importoptions">
        <h3><?php echo __('File to Import:'); ?></h3>
        <?php
        // zip, gzip and bzip2 encode features
        $compressions = array();

        if ($cfg['GZipDump'] && @function_exists('gzopen')) {
            $compressions[] = 'gzip';
        }
        if ($cfg['BZipDump'] && @function_exists('bzopen')) {
            $compressions[] = 'bzip2';
        }
        if ($cfg['ZipDump'] && @function_exists('zip_open')) {
            $compressions[] = 'zip';
        }
        // We don't have show anything about compression, when no supported
        if ($compressions != array()) {
            echo '<div class="formelementrow" id="compression_info">';
            printf(__('File may be compressed (%s) or uncompressed.'), implode(", ", $compressions));
            echo '<br />';
            echo __('A compressed file\'s name must end in <b>.[format].[compression]</b>. Example: <b>.sql.zip</b>');
            echo '</div>';
        }?>

        <div class="formelementrow" id="upload_form">
        <?php if ($GLOBALS['is_upload'] && !empty($cfg['UploadDir'])) { ?>
            <ul>
            <li>
                <input type="radio" name="file_location" id="radio_import_file" />
                <?php PMA_browseUploadFile($max_upload_size); ?>
            </li>
            <li>
                <input type="radio" name="file_location" id="radio_local_import_file" />
                <?php PMA_selectUploadFile($import_list, $cfg['UploadDir']); ?>
            </li>
            </ul>
        <?php } else if ($GLOBALS['is_upload']) {
            $uid = uniqid("");
            PMA_browseUploadFile($max_upload_size);
        } else if (!$GLOBALS['is_upload']) {
            PMA_Message::notice(__('File uploads are not allowed on this server.'))->display();
        } else if (!empty($cfg['UploadDir'])) {
            PMA_selectUploadFile($import_list, $cfg['UploadDir']);
        } // end if (web-server upload directory)
        ?>
        </div>

       <div class="formelementrow" id="charaset_of_file">
        <?php // charset of file
        if ($GLOBALS['PMA_recoding_engine'] != PMA_CHARSET_NONE) {
            echo '<label for="charset_of_file">' . __('Character set of the file:') . '</label>';
            reset($cfg['AvailableCharsets']);
            echo '<select id="charset_of_file" name="charset_of_file" size="1">';
            foreach ($cfg['AvailableCharsets'] as $temp_charset) {
                echo '<option value="' . htmlentities($temp_charset) .  '"';
                if ((empty($cfg['Import']['charset']) && $temp_charset == 'utf-8')
                        || $temp_charset == $cfg['Import']['charset']) {
                    echo ' selected="selected"';
                }
                echo '>' . htmlentities($temp_charset) . '</option>';
            }
            echo ' </select><br />';
        } else {
            echo '<label for="charset_of_file">' . __('Character set of the file:') . '</label>' . "\n";
            echo PMA_generateCharsetDropdownBox(PMA_CSDROPDOWN_CHARSET, 'charset_of_file', 'charset_of_file', 'utf8', false);
        } // end if (recoding)
        ?>
        </div>
    </div>
    <div class="importoptions">
        <h3><?php echo __('Partial Import:'); ?></h3>

        <?php
        if (isset($timeout_passed) && $timeout_passed) {
            echo '<div class="formelementrow">' . "\n";
            echo '<input type="hidden" name="skip" value="' . $offset . '" />';
            echo sprintf(__('Previous import timed out, after resubmitting will continue from position %d.'), $offset) . '';
            echo '</div>' . "\n";
        }
        ?>
        <div class="formelementrow">
            <input type="checkbox" name="allow_interrupt" value="yes"
                   id="checkbox_allow_interrupt" <?php echo PMA_pluginCheckboxCheck('Import', 'allow_interrupt'); ?>/>
            <label for="checkbox_allow_interrupt"><?php echo __('Allow the interruption of an import in case the script detects it is close to the PHP timeout limit. <i>(This might be good way to import large files, however it can break transactions.)</i>'); ?></label><br />
        </div>

        <?php
        if (! (isset($timeout_passed) && $timeout_passed)) {
            ?>
        <div class="formelementrow">
            <label for="text_skip_queries"><?php echo __('Number of rows to skip, starting from the first row:'); ?></label>
            <input type="text" name="skip_queries" value="<?php echo PMA_pluginGetDefault('Import', 'skip_queries');?>" id="text_skip_queries" />
        </div>
            <?php
        } else {
            // If timeout has passed,
            // do not show the Skip dialog to avoid the risk of someone
            // entering a value here that would interfere with "skip"
            ?>
        <input type="hidden" name="skip_queries" value="<?php echo PMA_pluginGetDefault('Import', 'skip_queries');?>" id="text_skip_queries" />
            <?php
        }
        ?>
    </div>

    <div class="importoptions">
        <h3><?php echo __('Format:'); ?></h3>
        <?php echo PMA_pluginGetChoice('Import', 'format', $import_list); ?>
        <div id="import_notification"></div>
    </div>

    <div class="importoptions" id="format_specific_opts">
        <h3><?php echo __('Format-Specific Options:'); ?></h3>
        <p class="no_js_msg" id="scroll_to_options_msg">Scroll down to fill in the options for the selected format and ignore the options for other formats.</p>
        <?php echo PMA_pluginGetOptions('Import', $import_list); ?>
    </div>
        <div class="clearfloat"></div>
    <?php
    // Encoding setting form appended by Y.Kawada
    if (function_exists('PMA_set_enc_form')) { ?>
        <div class="importoptions" id="kanji_encoding">
            <h3><?php echo __('Encoding Conversion:'); ?></h3>
            <?php echo PMA_set_enc_form('            '); ?>
        </div>
    <?php }
    echo "\n";
    ?>
	<div class="importoptions" id="check_tableselect">
		<h3><?php echo __('Custom Table Import :');?></h3>
		<input type="checkbox" name="checkselect" /><label for="checkselect">Import specific tables only. (SQL)</label>
	</div>
    <div class="importoptions" id="submit">
        <input type="submit" value="<?php echo __('Go'); ?>" id="buttonGo" />
    </div>
</form>
</div>
