# Sybil Framework
PHP Framework with MVC architecture

## Why a new PHP framework ?

I started working on this framework in 2013 after a few months using Symfony 2. I thought it was too big and I didn't need all those functionalities to build websites.
I needed a strong and useful framework, but smaller, so I started working on this one.
It's very inspired of Symfony methods (file structure, bundles ...), but I made my own classes to exactly fit my needs.

I stopped working on this framework a year ago, because I was missing time to continue.
But I let the project here, just in case anyone's interested to improve it.

The file structure is easy to understand and very close of Symfony file structure.
The framework files are located in /vendor/sybil/framework/src/Sybil directory.
Every class and method is documented with explicit comments.

The ORM works with schemas, located in each bundle directory.
You need to write a schema without references (see examples in the bundles I made), and execute a command (see the Command Class) to execute it.
The command will create the classes, the tables and generate the references (and will edit the schema to add them).
Then, you can update your table using the schemas (don't remove references of course), and relaunch the command to update your tables.

Making a complete documentation for this framework would be long, but it's not big and easy to understand.
If you have questions, feel free to contact me.

## TODO list
This is my TODO list, with the last things I wanted to do.

**ORM > MySQL**
- Reset of tables and columns references
- Order of columns (relative to the order set in the schema)
- Requests transactions
- Generate schemas and PHP classes from an existing database
- Basic model functions (save, remove, persist, search, find ...)

**ORM > MongoDB**
- Everything to do.

**CONSOLE**
- Web interface for using the functions without SSH access

**PARAMETERS AND SETTINGS**

Now using /app/Config.php file. 
The new configuration file already exists (/app/parameters.yml)
Just need to write the /vendor/sybil/framework/src/Sybil/Config.php class, and change calls to configuration variables in the framework files.

**FORM AND FORMBUILDER**
- To improve

**PUBLIC RESOURCES**

Everything to do. Framework class file already exists (Resource.php).
My goal was to use gulp in development mode with file caching (in /public/static/), and call these cache files in production mode.