# Opendatabio Version 0.9.0

OpenDataBio is web software to store, manage and distribute biodiversity and ecological data. It is designed to accommodate many data types used in taxonomy, systematics and ecology, allowing any information, including audio, image and video files, to be linked to Individuals, Vouchers, Locations and/or Taxons.
<br>
The main features of this database include:

1. **Unlimited creation of user defined variables** - [Traits](https://github.com/opendatabio/opendatabio/wiki/Trait-Objects#traits) of diferent types may be defined, including some special cases like Spectral Data, Colors and Links. Measurements for these traits can be stored for [individuals](https://github.com/opendatabio/opendatabio/wiki/Core-Objects#individuals), [vouchers](https://github.com/opendatabio/opendatabio/wiki/Core-Objects#vouchers), [taxonomic entities](https://github.com/opendatabio/opendatabio/wiki/Core-Objects#taxons) or [locations](https://github.com/opendatabio/opendatabio/wiki/Core-Objects#locations).
1. **Taxonomic validation with external APIs** - [Taxons](https://github.com/opendatabio/opendatabio/wiki/Core-Objects#taxons) may be published or unpublished names, synonyms or valid names, and any node of the tree of life may be stored. Compatible with both rank-based and [rankless](http://phylonames.org/code/) taxonomies. Taxon insertion is validated by APIs to different plant, fungi and animal data sources [Tropicos](https://www.tropicos.org), [IPNI](https://www.ipni.org/), [MycoBank](https://www.mycobank.org),[ZOOBANK](https://www.zoobank.org).
1. [Locations geometries](https://github.com/opendatabio/opendatabio/wiki/Core-Objects#locations). Locations, polygons or points, are stored with their Geometries, allowing spatial queries. Special location types, such as Plots allow to accomodate ForestPlots designs, and Conservation Units are treated as a special case as they may transverse different administrative areas.
1. **Data accessibility** - data are organized in [Datasets](https://github.com/opendatabio/opendatabio/wiki/Data-Access-Objects#datasets) and [Projects](https://github.com/opendatabio/opendatabio/wiki/Data-Access-Objects#projects), entities for which independent **user access-policies** may be defined, either restricted or public policies. These organized datasets may also recieve a public (Creative Commons) license and a dynamically generated citation, allowing to make them publicaly accessible, or accessible upon email requests to the data owner. These entities allow different research groups to use the same installation, having total control over their research data edition and access, while sharing common libraries such as Taxonomy, Locations, and Traits.
1. **External data access through a RESTful API service** - data exports and imports are possible through the [OpenDataBio API services](https://github.com/opendatabio/opendatabio/wiki/APi), along with a API client in the [R language](https://cran.r-project.org/), the [OpenDataBio-R package](https://github.com/opendatabio/opendatabio-r);
1. **Data changes are logged** - the [Activity model](https://github.com/opendatabio/opendatabio/wiki/Auditing) audits changes for history tracking, which are particularly usefull for taxonomic identification histories.
1. It is an opensource and free (see [License](license)).

See our [Wiki page](../../wiki) for a full documentation. Is is also included with translations within the App.


## Install

Installation instructions are provided in the [documents](https://github.com/opendatabio/opendatabio/wiki/Installation).
**Current version for develpment only, unstable.**

## Credits

(c) OpenDataBio Development team:

- Alberto Vicentini (vicentini.beto@gmail.com) - Instituto Nacional de Pesquisas da Amazônia ([INPA](http://portal.inpa.gov.br/)), Manaus, Brazil
- Andre Chalom (andrechalom@gmail.com)
- Rafael Arantes (birutaibm@gmail.com)
- Alexandre Adalardo de Oliveira (adalardo@usp.br) - Universidade de São Paulo (USP), Instituto de Biociências ([IB-USP](http://www.ib.usp.br/en/))

## Funding & Support
This project has received support from [Natura Campus](http://www.naturacampus.com.br/cs/naturacampus/home). Rafael Arantes contribution was supported by a FAPESP TTIV scholarship (#2017/21695-8).

## License
Opendatabio is licensed for use under a GPLv3 license.

Opendatabio is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
any later version.

Foobar is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with Foobar.  If not, see <https://www.gnu.org/licenses/>.

PHP is licensed under the PHP license. Composer and Laravel framework are licensed under the MIT license.

## Acknowledgements
- Rodrigo Augusto Santinelo Pereira (raspereira@ffclrp.usp.br)
- Lo Almeida
- Rodrigo Pereira
- Ricardo Perdiz
- Renato Lima
