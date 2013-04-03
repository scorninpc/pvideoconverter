<?php

// Classe de funções da interface do frmMain
class frmMain extends IfrmMain
{
	// Pipe para os comandos 
	private $pipe = NULL;
	
	// Armazena o iter que esta sendo convertido
	private $currentIter = NULL;
	
	// Armazena as configurações
	public $preferences = array();
	
	// Construtor
	public function __construct()
	{
		parent::__construct();
	}
	
	// Método executado ao iniciar a interface
	public function frmMain_onload()
	{
		// Inicia os threads
		$this->threads = array(FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE);
		
		// Popula as configurações
		$this->preferences['videoformat'] = "avi";
		$this->preferences['videocodec'] = "xvid";
		$this->preferences['optionalparameters'] = "-xvidencopts bitrate=300";
		
		// Inicia a aplicação
		$this->widgets['frmMain']->show_all();
		Gtk::main();
	}
	
	public function on_pipe_io_stdout($data, $thread)
	{
		// Verifica se existe conversão
		if($this->currentIter != NULL)
		{
			// Busca o model
			$model = $this->widgets['trvMain']->get_model();
				
			// Verifica se existe porcentagem
			if(($pos = strpos($data, "%)")) !== FALSE)
			{
				// Busca a porcentagem dentro do (xxx%)
				$posA = strpos($data, "(") + 1;
				$porcent = substr($data, $posA, $pos - $posA);
				
				// Atualiza a porcentagem do arquivo
				$model->set($this->currentIter[$thread], 1, trim($porcent));
			}
			
			// Verifica se é acabou a conversão
			if(strpos($data, "Flushing video frames.") !== FALSE)
			{
				// Marca como 100%
				$model->set($this->currentIter[$thread], 1, 100);
				
				// Libera o thread
				$this->threads[$thread] = FALSE;
				
				// Remove o iter corrent
				$this->currentIter[$thread] = NULL;
				
				// Atualiza o status
				$this->widgets['stsMain']->set_text("Thread " . $thread . " liberado");
			}
		}
	}
	
	// Método executado ao terminar a interface
	public function frmMain_unload()
	{
		// Termina a aplicação
		Gtk::main_quit();
		exit(0);
	}
	
	// Método que dropa os arquivos no treeview
	public function trvMain_ondrop($widget, $context, $x, $y, $data, $info, $time)
	{
		// Trata os arquivos e separa em um vetor
		$dropedData = str_replace("\r", "", $data->data);
		$dropedData = str_replace("file://", "", $dropedData);
		$files = explode("\n", $dropedData);
		
		// Percorre os arquivos
		foreach($files as $file)
		{
			// Verifica se existe o arquivo
			if((trim($file) != "") && (file_exists($file)))
			{
				// Separa o arquivo do diretório base
				$fileName = basename($file);
				$filePath = str_replace($fileName, "", $file);
				
				// Adiciona a linha
				$this->widgets['trvMain']->add_row(array($fileName, 0, $filePath));
			}
		}
	}
	
	// Método que inicia a conversão
	public function btnConvert_onclick()
	{
		// Busca o model
		$originalModel = $this->widgets['trvMain']->get_model();
		
		// Clona o model
		$model = $originalModel;
		
		// Percorre os arquivos à converter
		foreach($model as $row)
		{
			// Busca as informações do arquivo
			$fileName = $model->get_value($row->iter, 0);
			$filePorcent = $model->get_value($row->iter, 1);
			$filePath = $model->get_value($row->iter, 2);
			
			// Verifica se ja esta sendo ou ja foi convertido
			if($filePorcent > 0) {
				continue;
			}
			
			// Inicializa as variaveis
			$filters = "";
			
			// Atualiza o status
			$this->widgets['stsMain']->set_text("Convertendo o arquivo " . $fileName);
			
			// Monta o comando
/*
			$cmd  = "mencoder \"" . $filePath . $fileName . "\"";		// Arquivo à converter
			$cmd .= " -ovc " . $this->preferences['videocodec'];		// Codec de video
			$cmd .= " -of " . $this->preferences['videoformat'];		// Formato de video
			$cmd .= " -oac mp3lame";									// Codec de audio
			$cmd .= " -o \"" . $filePath . $fileName . "\".avi";		// Arquivo de saida
			$cmd .= " " . $this->preferences['optionalparameters'];		// Comandos opcionais
			$cmd .= " " . $filters;
*/
/*
			$cmd = "ffmpeg -sameq " . $filters . " crop=704:464:0:8 -i \"" . $filePath . $fileName . "\" \"" . $filePath . $fileName . ".avi\"";
*/
			$cmd = "mencoder \"" . $filePath . $fileName . "\" -ovc lavc -oac mp3lame -o \"" . $filePath . $fileName . "\".avi";
			
			// Armazena o comando executado
			file_put_contents(FULLPATH . "/lastcommand.log", file_get_contents(FULLPATH . "/lastcommand.log") . "\n" . $cmd);
			
			// Verifica o thread livre
			$threadKey = array_search(FALSE, $this->threads);
			
			// Armazena o iter que esta sendo convertido
			$this->currentIter[$threadKey] = $row->iter;
			
			// Inicia o pipe
			$pipe = Fabula::PipeIO("/bin/sh", array());
			$pipe->set_callback("stdout", array($this, "on_pipe_io_stdout"), $threadKey);
			$pipe->run();
			$pipe->write($cmd . "\n");
			
			// Seta o thread como ocupado
			$this->threads[$threadKey] = TRUE;
			
			// Espera pela proxima conversão
			while(array_search(FALSE, $this->threads) === FALSE)
			{
				while(Gtk::events_pending()) Gtk::main_iteration();
				sleep(0.5);
			}
		}
		
		// Atualiza o status
		$this->widgets['stsMain']->set_text("Todos os videos forão convertidos");
	}
	
	// Método de abertuda da tela para selecionar os arquivos
	public function btnOpen_onclick()
	{
		// Constri o dialogo
		$fileDialog = Fabula::GtkFileChooserDialog("Abrir arquivos para conversão", $this->widgets['frmMain'], FALSE);
		
		// Seta como seleção multipla
		$fileDialog->set_select_multiple(TRUE);
		
		// Inicia o dialogo
		$files = $fileDialog->run();
		
		// Verifica se foi selecionado ao menos um arquivo
		if($files !== FALSE)
		{
			// Percorre os arquivos
			foreach($files as $file)
			{
				// Verifica se existe o arquivo
				if((trim($file) != "") && (file_exists($file)))
				{
					// Separa o arquivo do diretório base
					$fileName = basename($file);
					$filePath = str_replace($fileName, "", $file);
					
					// Adiciona a linha
					$this->widgets['trvMain']->add_row(array($fileName, 0, $filePath));
				}
			}
		}
	}
	
	// Método para limpar a lista
	public function btnClear_onclick()
	{
		// Abre o dialogo da pergunta
		// Mostra o alerta
		$res = Fabula::GtkMessageDialog($this->widgets['frmMain'], Gtk::DIALOG_MODAL, Gtk::MESSAGE_QUESTION, Gtk::BUTTONS_YES_NO, "Deseja limpar a lista de videos?", TRUE);
		
		if($res->get_return() == Gtk::RESPONSE_YES)
		{
			// Limpa a lista
			$this->widgets['trvMain']->clear();
		}
	}
	
	// Método para cortar o video
	public function btnConfig_onclick()
	{
		
	}
}
