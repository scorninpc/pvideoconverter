<?php
	/**
	 * @author 		http://fabula.scorninpc.com/
	 * @package		Fabula IDE
	 * @subpackage	Dialogs
	 * @copyright	Copyright (C) 2010 Bruno Pitteli Gonçalves. All rights reserved.
	 * @license 	http://www.gnu.org/licenses/gpl.txt GNU/GPL version 3
	 * @version 	rev 1
	 */
	 
	// -----------------------------------------------------------------------------------------------------------------
	// Classe de dialogs
	// @since rev 1
	class FDialogs
	{
		// -------------------------------------------------------------------------------------------------------------
		// Método de criação de alertas
		// @since rev 1
		public function Alert($strMessage, $strTitle, $stockImage=Gtk::STOCK_DIALOG_WARNING)
		{
			// Cria o dialog
			// @since rev 1
			$dialog = new GtkDialog(
				$strTitle, 
				NULL, 
				Gtk::DIALOG_MODAL,
				array(
					Gtk::STOCK_OK, Gtk::RESPONSE_OK
				)
			);
			
			// Remove a opção de maximizar a janela
			// @since rev 1
			$dialog->set_resizable(FALSE);
			
			// Cria o stock
			// @since rev 1
			$stock = GtkImage::new_from_stock($stockImage, Gtk::ICON_SIZE_DIALOG);
			
			// Ajusta o layout
			// @since rev 1
			$dialog->vbox->pack_start($hbox = new GtkHBox(), 0, 0);
			$hbox->pack_start($stock, 0, 0);
			$hbox->pack_start(new GtkLabel($strMessage));
			$dialog->set_position(GTK::WIN_POS_CENTER);
			
			// Mostra o dialog
			// @since rev 1
			$dialog->show_all();
			
			// Aguarda o retorno
			// @since rev 1
			$response = $dialog->run();
			$dialog->destroy();
		}
		
		// -------------------------------------------------------------------------------------------------------------
		// Método de criação mensagens
		// @since rev 1
		public function MsgBox($strMessage, $strTitle, $buttons=array(Gtk::STOCK_OK, Gtk::RESPONSE_OK), $stockImage=Gtk::STOCK_DIALOG_WARNING)
		{
			// Cria o dialog
			// @since rev 1
			$dialog = new GtkDialog(
				$strTitle, 
				NULL, 
				Gtk::DIALOG_MODAL,
				$buttons
			);
			
			// Remove a opção de maximizar a janela
			// @since rev 1
			$dialog->set_resizable(FALSE);
			
			// Cria o stock
			// @since rev 1
			$stock = GtkImage::new_from_stock($stockImage, Gtk::ICON_SIZE_DIALOG);
			
			// Ajusta o layout
			// @since rev 1
			$dialog->vbox->pack_start($hbox = new GtkHBox(), 0, 0);
			$hbox->pack_start($stock, 0, 0);
			$hbox->pack_start(new GtkLabel($strMessage));
			$dialog->set_position(GTK::WIN_POS_CENTER);
			
			// Mostra o dialog
			// @since rev 1
			$dialog->show_all();
			
			// Aguarda o retorno
			// @since rev 1
			$response = $dialog->run();
			$dialog->destroy();
			
			// Retorna o path
			// @since rev 1
			return $response;
		}
		
		// -------------------------------------------------------------------------------------------------------------
		// Método de criação do dialog de seleção de arquivos
		// @since rev 1
		public function ChooseFile($strTitle, $filters=NULL, $type=Gtk::FILE_CHOOSER_ACTION_OPEN)
		{
			// Cria um dialogo do tipo FILE CHOOSER
			// @since rev 1
			$dialog = new GtkFileChooserDialog(
				$strTitle, 
				NULL, 
				$type, 
				array(Gtk::STOCK_OK, Gtk::RESPONSE_OK, Gtk::STOCK_CANCEL, Gtk::RESPONSE_CANCEL)
			);
			
			// Adiciona os filtros
			// @since rev 1
			if($filters != NULL)
			{
				$fileFilter = new GtkFileFilter();
				foreach($filters as $filter)
				{
					$fileFilter->add_pattern($filter);
				}
				$dialog->set_filter($fileFilter);
			}
			
			// Abre o dialogo
			// @since rev 1
			if($dialog->run() == "-5")
			{
				// Retorna o caminho do arquivo
				// @since rev 1
				$path = $dialog->get_filename();
			}
			else
			{
				// Retorna falso caso cancelar a janela
				// @since rev 1
				$path = FALSE;
			}
			
			// Destroi o dialogo e retorna o valor
			// @since rev 1
			$dialog->destroy();
			return $path;
		}
		
		// -------------------------------------------------------------------------------------------------------------
		// Método de criação de splashscreen
		// @since rev 1
		public function splashCreate($imagePath)
		{
			// Carrega a imagem 
			$pixbuf = GdkPixbuf::new_from_file($imagePath);
			
			// Cria uma janela popup
			$splash = new GtkWindow(GTK::WINDOW_POPUP);
			$splash->set_size_request($pixbuf->get_width(), $pixbuf->get_height());
			$splash->set_position(GTK::WIN_POS_CENTER);
			$splash->realize();
			
			// Adiciona a imagem no fundo do GtkWindow
			list($pixmap, $mask) = $pixbuf->render_pixmap_and_mask(255);
			$style = $splash->get_style();
			$style = $style->copy();
			$style->bg_pixmap[Gtk::STATE_NORMAL] = $pixmap;
			$splash->set_style($style);

			// Adiciona um fixed
			$splash->add(new GtkFixed());
			
			// Mostra o splash
			$splash->show_all();
			
			// Retorna o splash para futuras modificações
			return $splash;
		}
		
		// -------------------------------------------------------------------------------------------------------------
		// Método de criação de splashscreen
		// @since rev 1
		public function splashHide($splashScreen)
		{
			// Esconde o splash
			// @since rev 1
			$splashScreen->hide();
		}
	}
