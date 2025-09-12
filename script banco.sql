CREATE SCHEMA IF NOT EXISTS `mydb` DEFAULT CHARACTER SET utf8;
USE `mydb`;

-- -----------------------------------------------------
-- Table CLIENTE
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`CLIENTE` (
  `idCLIENTE` INT AUTO_INCREMENT NOT NULL,
  `nome` VARCHAR(100) NOT NULL,
  `cpf` VARCHAR(45) NOT NULL,
  `telefone` VARCHAR(20) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  PRIMARY KEY (`idCLIENTE`)
) ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table PROPRIETARIO
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`PROPRIETARIO` (
  `idPROPRIETARIO` INT AUTO_INCREMENT NOT NULL,
  `nome` VARCHAR(45) NOT NULL,
  `cpf` VARCHAR(45) NOT NULL,
  `telefone` VARCHAR(45) NULL,
  `email` VARCHAR(45) NULL,
  PRIMARY KEY (`idPROPRIETARIO`),
  UNIQUE INDEX `cpf_UNIQUE` (`cpf`)
) ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table IMOVEL
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`IMOVEL` (
  `idIMOVEL` INT AUTO_INCREMENT NOT NULL,
  `PROPRIETARIO_idPROPRIETARIO` INT NOT NULL,
  `tipo` VARCHAR(50) NULL,
  `rua` VARCHAR(150) NULL,
  `numero` VARCHAR(10) NULL,
  `bairro` VARCHAR(100) NULL,
  `cidade` VARCHAR(100) NULL,
  `estado` VARCHAR(2) NULL,
  `valor` DECIMAL(10,2) NULL,
  `descricao` VARCHAR(300) NULL,
  `status` ENUM('Vendido','Disponivel') NOT NULL,
  `qtd_quartos` INT NULL,
  `qtd_banheiro` INT NULL,
  `qtd_vagas` INT NULL,
  PRIMARY KEY (`idIMOVEL`),
  INDEX `fk_IMOVEL_PROPRIETARIO1_idx` (`PROPRIETARIO_idPROPRIETARIO`),
  CONSTRAINT `fk_IMOVEL_PROPRIETARIO1`
    FOREIGN KEY (`PROPRIETARIO_idPROPRIETARIO`)
    REFERENCES `mydb`.`PROPRIETARIO` (`idPROPRIETARIO`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION
) ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table CORRETOR
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`CORRETOR` (
  `idCORRETOR` INT AUTO_INCREMENT NOT NULL,
  `nome` VARCHAR(100) NOT NULL,
  `creci` VARCHAR(20) NOT NULL,
  `telefone` VARCHAR(20) NULL,
  PRIMARY KEY (`idCORRETOR`),
  UNIQUE INDEX `creci_UNIQUE` (`creci`)
) ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table VISITA
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`VISITA` (
  `idVISITA` INT AUTO_INCREMENT NOT NULL,
  `CORRETOR_idCORRETOR` INT NOT NULL,
  `IMOVEL_idIMOVEL` INT NOT NULL,
  `CLIENTE_idCLIENTE` INT NOT NULL,
  `data_visita` DATE NULL,
  `observacoes` TEXT NULL,
  PRIMARY KEY (`idVISITA`, `CLIENTE_idCLIENTE`),
  INDEX `fk_VISITA_CORRETOR1_idx` (`CORRETOR_idCORRETOR`),
  INDEX `fk_VISITA_IMOVEL1_idx` (`IMOVEL_idIMOVEL`),
  INDEX `fk_VISITA_CLIENTE1_idx` (`CLIENTE_idCLIENTE`),
  CONSTRAINT `fk_VISITA_CORRETOR1`
    FOREIGN KEY (`CORRETOR_idCORRETOR`)
    REFERENCES `mydb`.`CORRETOR` (`idCORRETOR`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_VISITA_IMOVEL1`
    FOREIGN KEY (`IMOVEL_idIMOVEL`)
    REFERENCES `mydb`.`IMOVEL` (`idIMOVEL`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_VISITA_CLIENTE1`
    FOREIGN KEY (`CLIENTE_idCLIENTE`)
    REFERENCES `mydb`.`CLIENTE` (`idCLIENTE`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION
) ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table PROPOSTA
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`PROPOSTA` (
  `idPROPOSTA` INT AUTO_INCREMENT NOT NULL,
  `VISITA_idVISITA` INT NOT NULL,
  `VISITA_CLIENTE_idCLIENTE` INT NOT NULL,
  `valor_ofertado` DECIMAL(10,2) NULL,
  `data_proposta` DATE NULL,
  `status` VARCHAR(45) NULL,
  PRIMARY KEY (`idPROPOSTA`),
  INDEX `fk_PROPOSTA_VISITA1_idx` (`VISITA_idVISITA`, `VISITA_CLIENTE_idCLIENTE`),
  CONSTRAINT `fk_PROPOSTA_VISITA1`
    FOREIGN KEY (`VISITA_idVISITA`, `VISITA_CLIENTE_idCLIENTE`)
    REFERENCES `mydb`.`VISITA` (`idVISITA`, `CLIENTE_idCLIENTE`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION
) ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table GESTOR
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`GESTOR` (
  `idGESTOR` INT AUTO_INCREMENT NOT NULL,
  `nome` VARCHAR(100) NOT NULL,
  `usuario` VARCHAR(50) NOT NULL,
  `senha` VARCHAR(255) NOT NULL,
  `email` VARCHAR(100),
  PRIMARY KEY (`idGESTOR`),
  UNIQUE INDEX `usuario_UNIQUE` (`usuario`)
) ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table IMAGEM_IMOVEL
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`IMAGEM_IMOVEL` (
  `idIMAGEM` INT AUTO_INCREMENT PRIMARY KEY,
  `IMOVEL_idIMOVEL` INT NOT NULL,
  `caminho` VARCHAR(255) NOT NULL,
  `nome_original` VARCHAR(255) NOT NULL,
  FOREIGN KEY (`IMOVEL_idIMOVEL`) REFERENCES `mydb`.`IMOVEL`(`idIMOVEL`) ON DELETE CASCADE
) ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table IMOVEL_PENDENTE
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`IMOVEL_PENDENTE` (
  `idIMOVEL_PENDENTE` INT AUTO_INCREMENT PRIMARY KEY,
  `nome_proprietario` VARCHAR(100) NOT NULL,
  `email_proprietario` VARCHAR(100) NOT NULL,
  `telefone_proprietario` VARCHAR(20) NOT NULL,
  `rua` VARCHAR(150) NOT NULL,
  `numero` VARCHAR(10) NOT NULL,
  `bairro` VARCHAR(100) NOT NULL,
  `cidade` VARCHAR(100) NOT NULL,
  `estado` VARCHAR(2) NOT NULL,
  `tipo` VARCHAR(50) NOT NULL,
  `finalidade` VARCHAR(50) NOT NULL,
  `descricao` VARCHAR(300) NOT NULL,
  `qtd_quartos` INT NOT NULL,
  `qtd_banheiro` INT NOT NULL,
  `qtd_vagas` INT DEFAULT 0,
  `area` DECIMAL(10,2) DEFAULT NULL,
  `valor` DECIMAL(10,2) NOT NULL,
  `negociavel` ENUM('sim','nao') NOT NULL,
  `data_envio` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table IMAGEM_IMOVEL_PENDENTE
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`IMAGEM_IMOVEL_PENDENTE` (
  `idIMAGEM_PENDENTE` INT AUTO_INCREMENT PRIMARY KEY,
  `IMOVEL_PENDENTE_id` INT NOT NULL,
  `caminho` VARCHAR(255) NOT NULL,
  `nome_original` VARCHAR(255) NOT NULL,
  FOREIGN KEY (`IMOVEL_PENDENTE_id`) REFERENCES `mydb`.`IMOVEL_PENDENTE`(`idIMOVEL_PENDENTE`) ON DELETE CASCADE
) ENGINE = InnoDB;

-- Tabela de mensagens de suporte
CREATE TABLE IF NOT EXISTS mensagens_suporte (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    telefone VARCHAR(20),
    mensagem TEXT NOT NULL,
    status ENUM('Pendente', 'Atendido') DEFAULT 'Pendente',
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);