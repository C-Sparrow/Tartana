# Tartana Faq

Most common questions answered by the Tartana team.

### [Error] exceeded the timeout of 60 seconds
Tartana is executing long running processes when downloading large files or extracting them. It tries it's best to disable the execution time limit. But on some hardened environments it is not allowed to disable it in the code. The settings must be disabled manually in the configuration files. On the console run the command

`php --ini`

This command will show you what for ini files are loaded. In one of them, mostly the *php.ini* file, set eh variables **max_execution_time** and **max_input_time** to **0**.

### Call to undefined function Pdp\idn_to_ascii()
On Synology, the default PHP binary on the command line has limited modules activated. You need to create symbolic links as described [here](synology.md).