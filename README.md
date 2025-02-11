# Asterisk IVR System

This project provides a IVR (Interactive Voice Response) system that interacts with an Asterisk server to process incoming requests and perform actions like playing audio, handling digit input, and recording messages.

## Instruction steps

To set up the project, follow these steps:

1. Clone the repository to your local machine:
   ```bash
   git clone https://github.com/mariakhachatryann/asterisk-ivr.git

2. Install the required dependencies using Composer
   ```bash
    composer install

3. Ensure your Asterisk server is up and running and that you have the appropriate credentials for connecting to it.


4. Set up the .env file with your Asterisk configuration (host, port, username, password, chanel, recording path)


## Routes

The system provides the following route for handling IVR requests:

- `POST /ivr`: Handles incoming digit input and triggers the appropriate actions based on the digit passed.
