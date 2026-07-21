-- ----------------------------------------------------------
-- MDB Tools - A library for reading MS Access database files
-- Copyright (C) 2000-2011 Brian Bruns and others.
-- Files in libmdb are licensed under LGPL and the utilities under
-- the GPL, see COPYING.LIB and COPYING files respectively.
-- Check out http://mdbtools.sourceforge.net
-- ----------------------------------------------------------

-- That file uses encoding UTF-8

CREATE TABLE `tbl_Aufbewahrung`
 (
	`AO_ID`			int not null auto_increment unique, 
	`AO_Aufbewahrungsort`			varchar (255)
);

-- CREATE INDEXES ...
ALTER TABLE `tbl_Aufbewahrung` ADD PRIMARY KEY (`AO_ID`);

CREATE TABLE `tbl_Datei`
 (
	`DA_ID`			int not null auto_increment unique, 
	`DA_Name`			varchar (255), 
	`DA_Beschreibung`			text, 
	`DA_Pfad`			varchar (255), 
	`DA_im_Web`			boolean NOT NULL, 
	`DA_geschützt`			boolean NOT NULL, 
	`DA_Schutz_bis`			varchar (255), 
	`Da_nur_digital`			boolean NOT NULL, 
	`DA_Ort_ID`			int, 
	`Da_Eingabedatum`			datetime, 
	`DA_Jahr`			varchar (255), 
	`DA_Änderungsdatum`			datetime
);

-- CREATE INDEXES ...
ALTER TABLE `tbl_Datei` ADD PRIMARY KEY (`DA_ID`);

CREATE TABLE `tbl_BEARBEITEN_DA`
 (
	`DA_Be_ID`			int not null auto_increment unique, 
	`DA_Be_Name`			varchar (255), 
	`DA_Be_Beschreibung`			text, 
	`DA_Be_Pfad`			varchar (255), 
	`DA_Be_im_Web`			boolean NOT NULL, 
	`DA_Be_geschützt`			boolean NOT NULL, 
	`DA_Be_Schutz_bis`			varchar (255), 
	`DA_Be_nur_digital`			boolean NOT NULL, 
	`DA_Be_Ort_ID`			int, 
	`Da_Be_Eingabedatum`			datetime, 
	`DA_Be_Jahr`			varchar (255), 
	`DA_Be_Änderungsdatum`			datetime
);

-- CREATE INDEXES ...
ALTER TABLE `tbl_BEARBEITEN_DA` ADD PRIMARY KEY (`DA_Be_ID`);

CREATE TABLE `tbl_Digital`
 (
	`DI_ID`			int NOT NULL, 
	`DI_Digitalstatus`			varchar (255)
);

-- CREATE INDEXES ...
ALTER TABLE `tbl_Digital` ADD PRIMARY KEY (`DI_ID`);

CREATE TABLE `tbl_Fach`
 (
	`FA_ID`			int not null auto_increment unique, 
	`FA_Namekurz`			varchar (255), 
	`FA_Namelang`			varchar (255), 
	`FA_Farbe`			varchar (255), 
	`FA_Beschreibung`			varchar (255)
);

-- CREATE INDEXES ...
ALTER TABLE `tbl_Fach` ADD PRIMARY KEY (`FA_ID`);

CREATE TABLE `tbl_IMPORT_DA`
 (
	`IM_PK_DA`			int not null auto_increment unique, 
	`IM_DA_Name`			varchar (255), 
	`IM_DA_Beschreibung`			text, 
	`IM_DA_Pfad`			varchar (255), 
	`IM_DA_im_Web`			boolean NOT NULL, 
	`IM_DA_geschützt`			boolean NOT NULL, 
	`IM_DA_Schutz_bis`			varchar (255), 
	`IM_DA_nur_digital`			boolean NOT NULL, 
	`IM_DA_Ort_ID`			int, 
	`IM_DA_Jahr`			varchar (255), 
	`IM_DA_Eingabedatum`			datetime
);

-- CREATE INDEXES ...
ALTER TABLE `tbl_IMPORT_DA` ADD PRIMARY KEY (`IM_PK_DA`);

CREATE TABLE `tbl_IMPORT_OB`
 (
	`IM_PK`			int not null auto_increment unique, 
	`IM_OB_ID`			int NOT NULL, 
	`IM_OB_Titel`			varchar (255) NOT NULL, 
	`IM_OB_Beschreibung`			text, 
	`IM_OB_Laufzeit_von`			varchar (4) NOT NULL, 
	`IM_OB_Datenschutz`			boolean NOT NULL, 
	`IM_OB_Quelle_ID`			int, 
	`IM_OB_Thema_ID`			int, 
	`IM_OB_Ort_ID`			int, 
	`IM_OB_Aufbewahrung_ID`			int, 
	`IM_OB_Regal_ID`			int, 
	`IM_OB_Fach_ID`			int, 
	`IM_OB_Platz_ID`			int, 
	`IM_OB_Digital_ID`			int, 
	`IM_OB_Art_ID`			int
);

-- CREATE INDEXES ...
ALTER TABLE `tbl_IMPORT_OB` ADD PRIMARY KEY (`IM_PK`);

CREATE TABLE `tbl_Objekt`
 (
	`OP_PK`			int not null auto_increment unique, 
	`OB_ID`			int NOT NULL, 
	`OB_Titel`			varchar (255) NOT NULL, 
	`OB_Beschreibung`			text, 
	`OB_Laufzeit_von`			varchar (4) NOT NULL, 
	`OB_LaufzeitvonErgänzung`			varchar (20), 
	`OB_Laufzeit_bis`			varchar (4), 
	`OB_LaufzeitbisErgänzung`			varchar (20), 
	`OB_Datenschutz`			boolean NOT NULL, 
	`OB_Quelle_ID`			int, 
	`OB_Thema_ID`			int NOT NULL, 
	`OB_Eingabedatum`			date, 
	`OB_Ort_ID`			int NOT NULL, 
	`OB_Aufbewahrung_ID`			int, 
	`OB_Rückgabe`			varchar (255), 
	`OB_Art_ID`			int NOT NULL, 
	`OB_Regal_ID`			int, 
	`OB_Fach_ID`			int, 
	`OB_Platz_ID`			int, 
	`OB_Anzahl`			int, 
	`OB_Digital_ID`			int NOT NULL, 
	`OB_Änderungsdatum`			date, 
	`OB_Dokumentendatum`			varchar (20), 
	`OB_Seiten`			smallint, 
	`OB_Schutz_bis`			varchar (255)
);

-- CREATE INDEXES ...
ALTER TABLE `tbl_Objekt` ADD PRIMARY KEY (`OP_PK`);

CREATE TABLE `tbl_Objektart`
 (
	`OA_ID`			int not null auto_increment unique, 
	`OA_Objektart`			varchar (255)
);

-- CREATE INDEXES ...
ALTER TABLE `tbl_Objektart` ADD PRIMARY KEY (`OA_ID`);

CREATE TABLE `tbl_Ort`
 (
	`OT_ID`			int not null auto_increment unique, 
	`OT_Name`			varchar (255) NOT NULL
);

-- CREATE INDEXES ...
ALTER TABLE `tbl_Ort` ADD PRIMARY KEY (`OT_ID`);

CREATE TABLE `tbl_Platz`
 (
	`PL_ID`			int not null auto_increment unique, 
	`PL_Namelang`			varchar (255), 
	`PL_Namekurz`			varchar (255), 
	`PL_Farbe`			varchar (255), 
	`PL_Beschreibung`			varchar (255), 
	`PL_Größe`			varchar (255)
);

-- CREATE INDEXES ...
ALTER TABLE `tbl_Platz` ADD PRIMARY KEY (`PL_ID`);

CREATE TABLE `tbl_Quelle`
 (
	`QU_ID`			int not null auto_increment unique, 
	`QU_Name`			varchar (255), 
	`QU_Vorname`			varchar (255), 
	`QU_Ort`			varchar (255), 
	`QU_PLZ`			int, 
	`QU_Anmerkung`			varchar (255), 
	`QU_ÜbergabeDatum`			datetime, 
	`QU_Str`			varchar (255)
);

-- CREATE INDEXES ...
ALTER TABLE `tbl_Quelle` ADD PRIMARY KEY (`QU_ID`);

CREATE TABLE `tbl_Regal`
 (
	`RE_ID`			int not null auto_increment unique, 
	`RE_Namekurz`			varchar (255), 
	`RE_Namelang`			varchar (255), 
	`RE_Farbe`			varchar (255), 
	`RE_Raum`			varchar (255), 
	`RE_Beschreibung`			varchar (255), 
	`RE_Höhe`			varchar (255), 
	`RE_Breite`			varchar (255), 
	`RE_Tiefe`			varchar (255)
);

-- CREATE INDEXES ...
ALTER TABLE `tbl_Regal` ADD PRIMARY KEY (`RE_ID`);

CREATE TABLE `tbl_Regalfach`
 (
	`RF_ID`			int not null auto_increment unique, 
	`RF_Regalfach_kurz`			varchar (255), 
	`RF_Regalfachname`			varchar (255), 
	`RF_Beschreibung`			varchar (255)
);

-- CREATE INDEXES ...
ALTER TABLE `tbl_Regalfach` ADD PRIMARY KEY (`RF_ID`);

CREATE TABLE `tbl_rel_OB_DA`
 (
	`ID_OB`			int NOT NULL, 
	`ID_DA`			int NOT NULL
);

-- CREATE INDEXES ...
ALTER TABLE `tbl_rel_OB_DA` ADD PRIMARY KEY (`ID_OB`, `ID_DA`);

CREATE TABLE `tbl_rel_OB_SW`
 (
	`ID_rel_TH_UT`			int not null auto_increment unique, 
	`ID_Thema`			int NOT NULL, 
	`ID_Objekt`			int NOT NULL
);

-- CREATE INDEXES ...
ALTER TABLE `tbl_rel_OB_SW` ADD PRIMARY KEY (`ID_rel_TH_UT`);

CREATE TABLE `tbl_Thema`
 (
	`TH_ID`			int NOT NULL, 
	`TH_Thema_Name`			varchar (255), 
	`TH_Thema_Beschreibung`			varchar (255)
);

-- CREATE INDEXES ...
ALTER TABLE `tbl_Thema` ADD PRIMARY KEY (`TH_ID`);

CREATE TABLE `tbl_Unterthema`
 (
	`UT_ID`			int not null auto_increment unique, 
	`UT_Unterthema`			varchar (255), 
	`UT_Thema_ID`			int
);

-- CREATE INDEXES ...
ALTER TABLE `tbl_Unterthema` ADD PRIMARY KEY (`UT_ID`);

CREATE TABLE `tbl_Filter`
 (
	`FI_ID`			int not null auto_increment unique, 
	`FI_OB_ID`			int
);

-- CREATE INDEXES ...
ALTER TABLE `tbl_Filter` ADD PRIMARY KEY (`FI_ID`);

CREATE TABLE `tbl_Thema_Kategorie`
 (
	`TH_KA_ID`			int NOT NULL, 
	`ID_TH`			int, 
	`ID_KA`			int
);

-- CREATE INDEXES ...
ALTER TABLE `tbl_Thema_Kategorie` ADD PRIMARY KEY (`TH_KA_ID`);


-- CREATE Relationships ...
