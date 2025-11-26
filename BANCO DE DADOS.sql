
CREATE SCHEMA IF NOT EXISTS `mydb` DEFAULT CHARACTER SET utf8mb4;
USE `mydb`;

-- -----------------------------------------------------
-- USUARIO
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `USUARIO` (
    `idUSUARIO` INT AUTO_INCREMENT PRIMARY KEY,
    `usuario` VARCHAR(50) NOT NULL UNIQUE,
    `senha` VARCHAR(255) NOT NULL,
    `tipo` ENUM('GESTOR','CORRETOR','admin') NOT NULL,
    `email` VARCHAR(100)
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- GESTOR
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `GESTOR` (
    `idGESTOR` INT AUTO_INCREMENT PRIMARY KEY,
    `USUARIO_idUSUARIO` INT NOT NULL,
    `nome` VARCHAR(100) NOT NULL,
    FOREIGN KEY (`USUARIO_idUSUARIO`) REFERENCES `USUARIO`(`idUSUARIO`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- CORRETOR
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `CORRETOR` (
    `idCORRETOR` INT AUTO_INCREMENT PRIMARY KEY,
    `USUARIO_idUSUARIO` INT NOT NULL,
    `nome` VARCHAR(100) NOT NULL,
    `creci` VARCHAR(20) NOT NULL UNIQUE,
    `telefone` VARCHAR(20),
    FOREIGN KEY (`USUARIO_idUSUARIO`) REFERENCES `USUARIO`(`idUSUARIO`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- CLIENTE
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `CLIENTE` (
    `idCLIENTE` INT AUTO_INCREMENT PRIMARY KEY,
    `nome` VARCHAR(100) NOT NULL,
    `cpf` CHAR(11) NOT NULL UNIQUE,
    `telefone` VARCHAR(20) NOT NULL,
    `email` VARCHAR(100) NOT NULL
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- PROPRIETARIO
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `PROPRIETARIO` (
    `idPROPRIETARIO` INT AUTO_INCREMENT PRIMARY KEY,
    `nome` VARCHAR(100) NOT NULL,
    `cpf` CHAR(11) NOT NULL UNIQUE,
    `telefone` VARCHAR(20),
    `email` VARCHAR(100)
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- IMOVEL
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `IMOVEL` (
    `idIMOVEL` INT AUTO_INCREMENT PRIMARY KEY,
    `PROPRIETARIO_idPROPRIETARIO` INT NOT NULL,
    `titulo` VARCHAR(100),
    `tipo` VARCHAR(50),
    `rua` VARCHAR(150),
    `numero` VARCHAR(10),
    `bairro` VARCHAR(100),
    `cidade` VARCHAR(100),
    `estado` VARCHAR(2),
    `valor` DECIMAL(10,2),
    `descricao` VARCHAR(300),
    `status` ENUM('Vendido','Disponivel') DEFAULT 'Disponivel',
    `qtd_quartos` INT,
    `qtd_banheiro` INT,
    `qtd_vagas` INT,
    `negociavel` ENUM('Sim','Não') DEFAULT 'Não',
    `financiavel` ENUM('Sim','Não') DEFAULT 'Não',
    FOREIGN KEY (`PROPRIETARIO_idPROPRIETARIO`) REFERENCES `PROPRIETARIO`(`idPROPRIETARIO`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- IMAGEM_IMOVEL
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `IMAGEM_IMOVEL` (
    `idIMAGEM` INT AUTO_INCREMENT PRIMARY KEY,
    `IMOVEL_idIMOVEL` INT NOT NULL,
    `caminho` VARCHAR(255) NOT NULL,
    `nome_original` VARCHAR(255) NOT NULL,
    FOREIGN KEY (`IMOVEL_idIMOVEL`) REFERENCES `IMOVEL`(`idIMOVEL`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- VISITA
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `VISITA` (
    `idVISITA` INT AUTO_INCREMENT,
    `CORRETOR_idCORRETOR` INT NOT NULL,
    `IMOVEL_idIMOVEL` INT NOT NULL,
    `CLIENTE_idCLIENTE` INT NOT NULL,
    `data_visita` DATE,
    `observacoes` TEXT,
    PRIMARY KEY (`idVISITA`, `CLIENTE_idCLIENTE`),
    FOREIGN KEY (`CORRETOR_idCORRETOR`) REFERENCES `CORRETOR`(`idCORRETOR`) ON DELETE CASCADE,
    FOREIGN KEY (`IMOVEL_idIMOVEL`) REFERENCES `IMOVEL`(`idIMOVEL`) ON DELETE CASCADE,
    FOREIGN KEY (`CLIENTE_idCLIENTE`) REFERENCES `CLIENTE`(`idCLIENTE`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- PROPOSTA
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `PROPOSTA` (
    `idPROPOSTA` INT AUTO_INCREMENT PRIMARY KEY,
    `VISITA_idVISITA` INT NOT NULL,
    `VISITA_CLIENTE_idCLIENTE` INT NOT NULL,
    `valor_ofertado` DECIMAL(10,2),
    `data_proposta` DATE,
    `status` VARCHAR(45),
    FOREIGN KEY (`VISITA_idVISITA`, `VISITA_CLIENTE_idCLIENTE`) 
        REFERENCES `VISITA`(`idVISITA`, `CLIENTE_idCLIENTE`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- MENSAGENS DE SUPORTE
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mensagens_suporte` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nome` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `telefone` VARCHAR(20),
    `mensagem` TEXT NOT NULL,
    `status` ENUM('Pendente','Atendido') DEFAULT 'Pendente',
    `data_criacao` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- HISTORICO DE AÇÕES (CORRIGIDA)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `HISTORICO` (
    `idHISTORICO` INT AUTO_INCREMENT PRIMARY KEY,
    `usuario_idUSUARIO` INT NOT NULL,
    `tabela` VARCHAR(50) NOT NULL,
    -- Ações padronizadas para o código PHP (INSERT, UPDATE, DELETE) mais login/logout
    `acao` ENUM('INSERT','UPDATE','DELETE','login','logout') NOT NULL,
    `registro_id` INT NOT NULL,
    `dados_anteriores` TEXT,
    -- CORRIGIDO: Coerente com o PHP
    `dados_atuais` TEXT,
    `data_hora` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`usuario_idUSUARIO`) REFERENCES `USUARIO`(`idUSUARIO`) ON DELETE CASCADE
) ENGINE=InnoDB;
