<?php
###################################################################################################
## Jon Wright, IBMS, Academia Sinica, Taipei, 11529, Taiwan
## These files are licensed under the GLP ver 3, essentially you have the right
## to copy, modify and distribute this script but all modifications must be offered
## back to the original authors
###################################################################################################
# This php ver. 7 script askes for a PDB ID and chain ID, it checks that they are 4 and 1 letter
# and then attemps to extract the chain from an archive version of the PDB entry
# It creates a working and a results directory and then queues a Conserv job
# Then it submits a conserv job to the server

# Retrieve the PDB and chain ids and check do a few simple checks on the inputs
$fasta = strtoupper($_POST["FASTA"]);

# Call the mkdirFunc and get the target, results directories and random number back
list($rand_target, $target_dir, $result_dir) = mkdirFunc();

# Make the list.txt file and save it to the target directory
$myfile = $target_dir . "input.fasta";
$listfile = fopen($myfile, "w");
fwrite($listfile, $fasta);
fclose($listfile);
$errfile = $target_dir . "error.txt"; # write something to the error.txt file
$errfile_handle = fopen($errfile, "w");
fwrite($errfile_handle, "Preparing and checking the input files\n");
fclose($errfile_handle);

# Now submit the job to the qeuue system
if ($ret_var == 0) {
    echo "We will now queue the Conserv job, please wait a few seconds to be directed to the running/results page.<br>";
    exec('/usr/local/bin/qsub -S /bin/bash /var/www/html/conserv/scripts/submit_fasta.sub -N C_' . $rand_target . ' -v "random=' . $rand_target . '" > ' . $result_dir . 'jobid.txt');
    symlink($target_dir . 'error.txt', $result_dir . 'error_link.txt');
} else {
    exec('rsync -av ' . $target_dir . ' ' . $result_dir);
    exec('echo 999999.limlab >| ' . $result_dir . 'jobid.txt');
}
echo "<meta http-equiv=\"refresh\" content=\"5; URL=http://conserv.limlab.dnsalias.org/results/$rand_target\" />";

# This function makes a unique random number directory in /scratch and results
function mkdirFunc() {
    mkdirloop:
        $rand_target = rand(1, 1000000);
        $target_dir = "/scratch/working/" . $rand_target . "/";
        $result_dir = "/var/www/html/conserv/results/" . $rand_target . "/";
        $dir_exists = (is_dir($target_dir));
        if ($dir_exists == false) {
            mkdir($target_dir, 0700);
            mkdir($result_dir, 0700);
            symlink("/var/www/html/conserv/scripts/index.php", "$result_dir/index.php");
        } else {
            gotomkdirloop;
        }
        return array($rand_target, $target_dir, $result_dir);
    }
?>
