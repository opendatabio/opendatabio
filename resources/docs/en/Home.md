The OpenDatabBio project aims to provide a flexible but robust framework for storing, analyzing and exporting biological data. It is specially focused on plant biology, but it can also be used for other taxa. The main features of this database include the ability to define custom [[Traits|Trait]] that can be measured on individual marked plants, herbarium specimens, taxonomic entities and localities. Data can be organized in Datasets and Projects, and have different types of users and data access permissions.  Tools to data management, exports and imports, are provided along with a R package and API services. You can check the full [[Data Model]] specification.

The code and documentation are being written in English to expand the possibilities of cooperation with other research groups, but the interface should be translatable to other languages, most notably Portuguese and Spanish.

The project should have a main module for querying the database directly, including new data and editing or deleting existing data. The data entry on this module will consist on predetermined pages for the base objects and on flexible [[Forms|Interface and data access]] to enter used defined variables. It should also allow for flexible searches on all database objects, using the defined fields or user supplied Traits. These searches may be saved as user defined data filters. These should be detailed later (TODO).

There will also be modules for the bulk import and export of data.

The data import should accept csv and Excel spreadsheets, and must handle different field and numeric separators, date formats, encodings and line endings.

The data export module should allow for taxonomic filters, as well as filters by censuses, date, location and should have a helper to include all the traits contained in a Form. The data export should de-normalize measurements for categorical traits with multiple select. There should be a tool for exporting all types of objects, including taxonomic reports, plant data and voucher data.

The data access API should be documented and allow for recovery and insertion of data. This is specially relevant for the companion R package, and for importing data via Open Data Kit forms (see below).

There should be a provision for including spectral data (one value of reflection or absorption for each wavelength) and molecular data (one id (genebank?), one marked and the sequence) in the database.

Other than the core facilities of the system, more functionality may be provided as plugins. An extensive documentation will be provided on how to specify a plugin.

A companion R package is also being developed to improve the data import and export routines. The data validation for importing should consider: ignoring whitespace difference; ignoring accents; ignoring case; stemming of words and fuzzy matching, with the later being suggested as edits. It should be allowed to save the import job in any step and returning later with no data loss. Trimming and 'nulling' are being handled by [[Laravel|https://laravel.com/docs/5.4/requests#input-trimming-and-normalization]]. Fuzzy Matching is being implemented by a longest matching block heuristics, based on Ratcliff & Obershelp (1980) via the [[FuzzyWuzzy|https://github.com/wyndow/fuzzywuzzy]] library. 

## Authors

**Coordinator:** 
- Alberto Vicentini (vicentini.beto@gmail.com, alberto.vicentini@inpa.gov.br) - Instituto Nacional de Pesquisas da Amazônia ([INPA](http://portal.inpa.gov.br/)), Manaus, Brazil

**Collaborators:**
- Andre Chalom (andrechalom@gmail.com) - Main developer 
- Alexandre Adalardo de Oliveira (adalardo@usp.br) - Universidade de São Paulo (USP), Instituto de Biociências ([IB-USP](http://www.ib.usp.br/en/))

## Funding & Support
This project has received support from [Natura Campus](http://www.naturacampus.com.br/cs/naturacampus/home).
