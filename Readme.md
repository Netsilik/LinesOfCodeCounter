Lines of Code counter
=====================

Simple utility script for Counting Lines of Code.

---

European Union Public Licence, v. 1.1

Unless required by applicable law or agreed to in writing, software
distributed under the Licence is distributed on an "AS IS" basis,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.

Contact: info@netsilik.nl  
Latest version available at: https://gitlab.com/Netsilik/Config


Installation
------------

```
composer require netsilik/lines-of-code-counter
```

Usage
-----

Lince of Code counter is a command line scipt:


```bash
Usage: loc [OPTION]... DIRECTORY...
Count the lines of code in the files in the specified DIRECTORY(ies).

Mandatory arguments to long options are mandatory for short options too.
  -f, --file-mask              Process only files that match the file mask
      --help                   Display this help and exit
  -i, --ignore-dir=DIRECTORY   Ignore all files in the directory DIRECTORY
  -r, --recursive              Recursively process filse in sub-directories
      --version                Output version information and exit
```
