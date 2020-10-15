<?php
###################################################################################################
## Jon Wright, IBMS, Academia Sinica, Taipei, 11529, Taiwan
## These files are licensed under the GLP ver 3, essentially you have the right
## to copy, modify and distribute this script but all modifications must be offered
## back to the original authors
###################################################################################################
# The is the master index file for the Conserv Webserver - it is based on php ver. 7,
# If a job is queued or running it tells yu anf gives the position and refreshes each minute
# If it is finished then it creates the gnuplot file and asembles the lines
# for jsmol

# The main driver code is here, we get the jobid and then check the queue system to see if it is
# Queded or Running or exiting, if it is neither then we check for a results file and if there is one we assume it is finished
# If it is not in the queue system and the results file is empty we assume it has failed
#
# Open the random.txt file to get the job number from the file saved when it was submitted
$jobfile = fopen("jobid.txt", "r") or die("Unable to open file!");
$jobid = fgets($jobfile);
fclose($jobfile);
# Get the status and give the status as the header
$my_temp = shell_exec("/usr/local/bin/qstat | grep $jobid");
$job_status = preg_split('/\s+/', $my_temp);
$found = isset($job_status[4]); # if the job is in the queue this will be set
if ($found == true) {
    if ($job_status[4] == "Q") {
        queuedup($jobid);
    } elseif ($job_status[4] == "R" || $job_status[4] == "E") {
        running($jobid);
    } else { # Job was not in the queue system, ideally we should never get to this line
        failed($jobid);
    }
} elseif (filesize("conservation.txt") != 0) { # Check for a conservation.txt file, if it exists and is not zero then we have finished
    finished($jobid);
} else { # IF we are here then the job is not in the queue and the conservation.txt file is empty so we failed!
    failed($jobid);
}

# The functions sections
# The status function is called from both the queued and running jobs to give feedback to the user
# about what point their job has reached., this works by grepping the error_link.txt file for lines
# that are printed out as the job progresses
function status() {
    echo "Prepared input file: ";
    exec('grep "Preparing and checking the input files" error_link.txt', $out, $ret_val);
    if ($ret_val == 0) {
        echo "&#9745<br>";
    } else {
        echo "&#9744<br>";
    }
    echo "Started Conservation analysis: ";
    exec('grep "Starting the Consurf calculations" error_link.txt', $out, $ret_val);
    if ($ret_val == 0) {
        echo "&#9745<br>";
    } else {
        echo "&#9744<br>";
    }
    echo "Searching the UniRef90 for homologs: ";
    exec('grep "Jackhmmering the Uniref90 DB" error_link.txt', $out, $ret_val);
    if ($ret_val == 0) {
        echo "&#9745<br>";
    } else {
        echo "&#9744<br>";
    }
    echo "Selecting sequences for alignment: ";
    exec('grep "Running select_seqs to rejecting some sequences" error_link.txt', $out, $ret_val);
    if ($ret_val == 0) {
        echo "&#9745<br>";
    } else {
        echo "&#9744<br>";
    }
    echo "Calculating the final grades: ";
    exec('grep "Running rate4site and grading the scores" error_link.txt', $out, $ret_val);
    if ($ret_val == 0) {
        echo "&#9745<br>";
    } else {
        echo "&#9744<br>";
    }
    echo "<meta http-equiv=\"refresh\" content=\"60\"/>";
}

# The function for if a job is missing or failed
function failed($jobid) {
    echo "<head>";
    echo "<title>::: Failed at the Conserv server :::</title>";
    echo "<meta charset=\"utf-8\">";
    echo "</head>";
    echo "<body BGCOLOR=\"#FFFFFF\">";
    echo "<center> <img src=\"../../images/as-en_07.gif\" alt=\"Academia Sinica Logo\">";
    echo "<h2>Welcome to Conserv, the residue conservation score server.";
    echo "</center>";
    echo "<H2>Job $jobid is Missing for some reason.</H2>";
    if (filesize("error.txt") != 0 || filesize("conserv.err") != 0) {
        echo "To try to get an idea what is wrong<br>";
    }
    if (filesize("error.txt") != 0) {
        echo "You can try looking at the <a href=\"error.txt\">error.txt</a> file,<br>";
    }
    if (filesize("conserv.err") != 0) {
        echo "You can try looking at the <a href=\"conserv.err\">conserv.err</a> file<br> ";
    }
}

# The function for if we find a job is queued
function queuedup($jobid) {
    echo "<head>";
    echo "<title>::: Queued  at the Conserv residue server :::</title>";
    echo "<meta charset=\"utf-8\">";
    echo "</head>";
    echo "<body BGCOLOR=\"#FFFFFF\">";
    echo "<center> <img src=\"../../images/as-en_07.gif\" alt=\"Academia Sinica Logo\">";
    echo "<h2>Welcome to Conserv, the residue conservation score server.";
    echo "</center>";
    echo "<H2>Your job is $jobid and is currently in the queue for calculation.</H2>";
    echo "This page will be updated every minute";
    # Find queue status
    echo "<pre>Q order  Q number                  Q Name<br></pre>";
    $my_status = shell_exec("/usr/local/bin/qstat | nl -v -2 | grep apache");
    echo "<pre>$my_status</pre>";
    status(); # Call the status function
}

# The function for if we find a job is running
function running($jobid) {
    echo "<head>";
    echo "<title>::: Running  at the Conserv residue server :::</title>";
    echo "<meta charset=\"utf-8\">";
    echo "</head>";
    echo "<body BGCOLOR=\"#FFFFFF\">";
    echo "<center> <img src=\"../../images/as-en_07.gif\" alt=\"Academia Sinica Logo\">";
    echo "<h2>Welcome to Conserv, the residue conservation score server.";
    echo "</center>";
    echo "<H2>Your job is $jobid and is currently running.</H2>";
    echo "This page will be updated every minute";
    # Find my job and print it out
    echo "<pre>Q order  Q number                  Q Name<br></pre>";
    $my_status = shell_exec("/usr/local/bin/qstat | nl -v -2 | grep apache");
    echo "<pre>$my_status</pre>";
    status(); # Call the status function
}

# The function for if we find a job is finished
function finished($jobid) {
    # array to hold the uppercase to lowercase resname
    echo "<head>";
    echo "<title>::: Finished  at the Conserv residue server :::</title>";
    echo "<meta charset=\"utf-8\">";
    echo "</head>";
    echo "<body BGCOLOR=\"#FFFFFF\">";
    echo "<center> <img src=\"../../images/as-en_07.gif\" alt=\"Academia Sinica Logo\">";
    echo "<h2>Welcome to Conserv, the residue conservation score server.";
    echo "</center>";
    # Now reate the webpage itself
    echo "Your job has finished and the results are available.<br>";
    echo "<p>The conservation grades can be download from <a href=\"conservation.txt\">here</a>, this also includes the frequency of each residue type at each position ";
    echo "<pre>";
    echo file_get_contents( "conservation.txt" ); // get the contents, and echo it out.
    echo "</pre>";
    echo "<hr style=\"border-style: solid; color: black;\">";
    echo "<a href=\"https://conserv.limlab.dnsalias.org\">Conserv</a> is hosted at <a href=\"http://www.ibms.sinica.edu.tw\">The Institute of Biomedical Sciences</a>, <a href=\"http://www.sinica.edu.tw\">Academia Sinica</a>, Taipei 11529, Taiwan.";
    echo "<hr style=\"border-style: solid; color: black;\">";
}
