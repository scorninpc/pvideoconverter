<?php

// Cria o path da aplicação
define("FULLPATH", dirname(__FILE__));

// Cria o path das bibliotecas
define("LIBPATH", FULLPATH . "/libraries");

// Cria o path das imagens
define("IMAGESPATH", FULLPATH . "/images");

// Cria o path das interfaces
define("INTERFACESPATH", FULLPATH . "/interfaces");

// Seta a codificação do programa
ini_set("php-gtk.codepage", "UTF-8");

// Configura o tema da interface para mostrar os icones nos botões
$new = GtkSettings::get_default();
$new->set_long_property("gtk-button-images", TRUE, 0);

// Inclui o framework Fabula
require_once(LIBPATH . "/Fabula/Fabula.class.php");

// Inclui a classe de manipulação de video
//require_once(LIBPATH . "phpvideotoolkit/");

// Inclui o frmMain
require_once(INTERFACESPATH . "/frmMain.interface.php");
require_once(FULLPATH . "/frmMain.php");

// Inicia a aplicação
new frmMain();
