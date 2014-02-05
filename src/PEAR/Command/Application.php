<?php

/**
 * PEAR_Command_Application
 *
 * NOTICE OF LICENSE
 *
 * PEAR_Command_Application is free software: you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * PEAR_Command_Application is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with PEAR_Command_Application. If not, see <http://www.gnu.org/licenses/>.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PEAR_Command_Application
 * to newer versions in the future. If you wish to customize PEAR_Command_Application
 * for your needs please refer to http://www.techdivision.com for more information.
 *
 * @category   PEAR_Command
 * @package    PEAR_Command_Application
 * @copyright  Copyright (c) 2009 <tw@techdivision.com> Tim Wagner
 * @license    <http://www.gnu.org/licenses/>
 * 			   GNU General Public License (GPL 3)
 */

PEAR::setErrorHandling(PEAR_ERROR_DIE);

require_once 'PEAR/Command/Common.php';
require_once 'PEAR/PackageFile/Parser/v2.php';
require_once 'PEAR/PackageFileManager2.php';
require_once 'PEAR/PackageFileManager/File.php';

/**
 * A PEAR Command that creates the contents node in a package.xml
 * file that will be used as template.
 *
 * @category   PEAR_Command
 * @package    PEAR_Command_Application
 * @copyright  Copyright (c) 2009 <tw@techdivision.com> Tim Wagner
 * @license    <http://www.gnu.org/licenses/>
 * 			   GNU General Public License (GPL 3)
 * @author     Tim Wagner <tw@techdivision.com>
 */
class PEAR_Command_Application extends PEAR_Command_Common
{

    /**
     * The commands implemented in this class.
     * @var array
     */
    public $commands = array(
        'contents' => array(
            'summary' => 'Creates the contents node in an extisting package.xml',
            'function' => 'doContents',
            'shortcut' => 'ctnt',
            'options' => array(
                'templatefile' => array(
                    'shortopt' => 'T',
                    'doc' => 'Path to template package2.xml',
                    'arg' => 'TEMPLATEFILE',
                ),
                'srcdir' => array(
                    'shortopt' => 'S',
                    'doc' => 'Path to source code folder.',
                    'arg' => 'SRCDIR',
                ),
                'destinationdir' => array(
                    'shortopt' => 'D',
                    'doc' => 'Path to destination folder where to save the generated package2.xml',
                    'arg' => 'DESTINATIONDIR',
                ),
                'dirroles' => array(
                    'shortopt' => 'R',
                    'doc' => 'Roles configuration e.g. directory:role;directory:role;...',
                    'arg' => 'DIRROLES',
                )
            ),
            'doc' => '[descfile] Creates the contents node in an existing package.xml.'
        )
    );

    /**
     * The options passed from the command line when excecuting the command.
     * @var array
     */
    protected $_options = array();

    /**
     * Output written after finishing the command execution.
     * @var string
     */
    protected $_output = '';

    /**
     * Depending on current source folder structure this method generates
     * automatically the contents node in package2.xml
     *
     * @param $command
     * @param $options
     * @param $params
     * @return void
     */
    public function doContents($command, $options, $params)
    {
    	// get options passed from the command line
    	$this->_options = $options;
    	// initialize the result
    	$result = null;
    	// init the directory roles
    	$dirRoles = array();
    	// check all options
        if (!isset($this->_options['templatefile'])) {
        	$result = new PEAR_Error(
        		'No templatefile given. Please use option -T'
        	);
        }
        if (!isset($this->_options['srcdir'])) {
        	$result = new PEAR_Error(
        		'No sourcedir given. Please use option -S'
        	);
        }
        if (!isset($this->_options['destinationdir'])) {
            $result = new PEAR_Error(
            	'No destinationdir given. Please use option -D'
            );
        }
        if (isset($this->_options['dirroles'])) {
        	$optionDirRoles = $this->_options['dirroles'];
        	try {	        
	        	foreach (explode(";", $optionDirRoles) as $data) {
	        		list($optionDirRoleDirectory, $optionDirRole) = explode(":", $data);
	        		$dirRoles[$optionDirRoleDirectory] = $optionDirRole;
	        	}
        	} catch (Exception $e) {
        		$result = new PEAR_Error(
            		'Roles configuration is not correct. Please use -R directory:role;directory:role;...'
            	);
        	}
        }
        // display error if optioncheck failed
        if ($result instanceof PEAR_Error) {
            return $result;
        }
        // set vars by options
        $templateFile = $this->_options['templatefile'];
        $srcDir = $this->_options['srcdir'];
        $destinationDir = $this->_options['destinationdir'];
        // check if template file exists
        if (!file_exists($templateFile)) {
            $this->raiseError(
            	'Could not find template file: ' . $templateFile
            );
        }
        // check if sourcecode dir is there
        if (!is_dir($srcDir)) {
            $this->raiseError(
            	'Could not find code source dir: ' . $srcDir
            );
        }
        // check if destination dir is there
        if (!is_dir($destinationDir)) {
            $this->raiseError(
            	'Could not find destination save dir: '. $destinationDir
            );
        }
        // initialize the parser for the package file and parse it
        $pkg = new PEAR_PackageFile_Parser_v2();
        // pass the PEARConfig instance
        $pkg->setConfig($this->config);
        // load the data from the template
        $data = file_get_contents($templateFile);
        // parse the template and create new PEAR_PackageFileManager2 instance
        $pfm = $pkg->parse(
            $data, $templateFile, false, 'PEAR_PackageFileManager2'
        );
        // overwrite the default PHP role
        $roles = array('php' => 'www');
        // set the directory roles
        $dirRoles['app/code/core'] = 'www';
        $dirRoles['app/code/community'] = 'www';
        $dirRoles['app/code/local'] = 'www';
        $dirRoles['www'] = 'www';
        // append options configuration for directory roles
        
        // set the options
        $pfm->setOptions(
            array(
            	'packagedirectory'  => $srcDir,
            	'baseinstalldir'    => '/',
                'outputdirectory'   => $destinationDir,
            	'ignore'			=> array('package.xml', 'package2.xml'),
               	'simpleoutput'	    => true,
               	'roles'				=> $roles,
               	'dir_roles'			=> $dirRoles
            )
        );
        
        /**
         * @see http://www.laurent-laville.org/pear/PEAR_PackageFileManager/docs/TDG/en/ch09s03.html#tutorial.lesson5.tasks.postinstall.paramgroup
         * 
         * $task = $pfm->initPostinstallScript('setup/setup.php');
         * $task->addParamGroup('setup', array());
         * $pfm->addPostinstallTask($task, 'setup/setup.php');
         */
        
        // generate the contents node and write the file
    	$pfm->generateContents();
        $pfm->writePackageFile();
        // write a log message
    	$this->_output = 'Successfully generated package file';
    	// delegate the messages to the user interface
        if ($this->_output) {
            $this->ui->outputData($this->_output, $command);
        }
    }
}