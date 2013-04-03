<?php

/**
 * a pipe is a "channel" built to talk between your php script and an external command (shell, script or what ever)
 *  - you can "talk" to this command : just write to file descriptor  (see PipeIO::write()) (stdin)
 *  - you can read from this command,  (stdout)
 *  - there is also an channel for errors : stderr
 *
 * When process is terminated, pipe is terminated, you need to close it, (in actual source, does not work properly)
 * 
 * 
 * 
 * @Note I cannot found the author from this classes. If you are the author, please, contact me via scorninpc@gmail.com
 * 
 * 
 * 
 */
class PipeIO
{
	protected $descriptor_spec;
	protected $cmd;
	protected $args;
	protected $pipes;

	protected $stdin_data;
	protected $stdout_data;
	protected $stderr_data;

	protected $terminated;
	protected $callback;

	public function __construct()
	{
		$this->descriptor_spec = array(
			0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
			1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
			2 => array("pipe", "w")   // stderr is a file to write to
		);
		$this->proc_id = NULL;
		$this->callback = array(
			'stdout'	=> null,
			'stderr'	=> null,
			'hup'		=> null
		);
	}

	public function __destruct()
	{
		$this->terminate();
	}

	public function terminate()
	{
		if($this->proc_id != NULL)
		{
			proc_close($this->proc_id);
			$this->proc_id = NULL;
		}
	}

	public function write($txt)
	{
		if(!is_resource($this->pipes[0]))
		{
			return FALSE;
		}
		
		fwrite($this->pipes[0], $txt);
	}

	public function flush()
	{
		if(!is_resource($this->pipes[0]))
		{
			return FALSE;
		}
		
		fflush($this->pipes[0]);
	}

	public function set_callback($signal, $function, $param)
	{
		$this->callback[$signal] = $function;
		$this->params[$signal] = $param;
	}
	
	public function get_pid()
	{
		$status = proc_get_status($this->proc_id);
		
		return $status['pid'];
	}
}

/**
 * This pipe is managed with Gtk::io_add_watch() : when data is ready to read, a callback is called with right file descriptor.
 * You just need to read data
 * This class handle user callback for : reading, stderr, and when pipe is closed ; just register to those callback.
 * Main loop is handled by Gtk::main(). Gtk will check file descriptor and notify you when needed.
 * 
 * 
 * 
 * 
 * @Note I cannot found the author from this classes. If you are the author, please, contact me via scorninpc@gmail.com
 * 
 * 
 *
 */
class Pipe extends PipeIO
{
	protected $command;
	protected $options;

	public function __construct($command, $options=array())
	{
		parent::__construct();
		$this->command = $command;
		$this->options = $options;
	}
	
	public function set_command($command, $options) {
		$this->command = $command;
		$this->options = $options;
	}

	public function run()
	{
		$this->pipes = NULL;

		$cmd = $this->command . " " . join(" ", $this->options);

		$this->proc_id = proc_open($cmd, $this->descriptor_spec, $this->pipes);

		# could set non blocking IO here
		# stream_set_blocking($this->pipes[1], false);  # stdout
		# stream_set_blocking($this->pipes[2], false);  # stderr
		if ($this->proc_id !== FALSE) 
		{
			$ids[] = Gtk::io_add_watch($this->pipes[1],Gobject::IO_HUP, array($this, 'io_hup'));
			$ids[] = Gtk::io_add_watch($this->pipes[1],Gobject::IO_IN,  array($this, 'stdout'));
			$ids[] = Gtk::io_add_watch($this->pipes[1],Gobject::IO_ERR, array($this, 'stderr'));
		} 
		else
		{
			throw new Exception("PipeIO::run() : proc_open error");
		}
	}

	public function io_hup($pipe)
	{
		$this->terminate();

		if($this->callback['hup'] != NULL)
		{
			call_user_func($this->callback['hup'], $this->params['hup']);
		}
	}

	public function stdout($pipe)
	{
		if(!is_resource($this->pipes[1]))
		{
			return FALSE;
		}

		# here avoid blocking read : don't do fread until "\n" for example or set stream_set_blocking()
		$data = fread($pipe, 1024*32);
		
		if($this->callback['stdout'] != null)
		{
			call_user_func($this->callback['stdout'], $data, $this->params['stdout']);
		}

		return TRUE;
	}

	public function stderr($pipe)
	{
		if(!is_resource($this->pipes[1]))
		{
			return FALSE;
		}

		# here avoid blocking read : don't do fread until "\n" for example.
		$data = fread($pipe, 1024*32);
		if($this->callback['stderr'] != NULL)
		{
			call_user_func($this->callback['stderr'], $data, $this->params['stderr']);
		}

		return TRUE;
	}
	
	function send_signal($signal) { 
		$command = $this->command;
		
		$status = proc_get_status($this->proc_id);
		$startpid = $status['pid'];
		
		$limit = 3;
		
		$ps = `ps --sort=pid -o comm= -o pid=`; 
		$ps_lines = explode("\n", $ps); 

		$pattern = "/(\S{1,})(\s{1,})(\d{1,})/"; 

		foreach($ps_lines as $line) 
		{ 
			if(preg_match($pattern, $line, $matches)) 
			{ 
				//this limits us to finding the command within $limit pid's of the parent; 
				//eg, if ppid = 245, limit = 3, we won't search past 248 
				if($matches[3] > $startpid + $limit) 
					break; 

				//try to match a ps line where the command matches our search 
				//at a higher pid than our parent 
				if($matches[1] == $command && $matches[3] > $startpid)  {
					//return $matches[3]; 
					system("kill -" . $signal . " " . $matches[3]);
					
					return true;
				}
			} 
		} 

		return false; 
	} 
}
