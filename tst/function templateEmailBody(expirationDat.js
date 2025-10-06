function templateEmailBody(expirationDateString, isBurnafterreading)
        {
            const EOL = '\n';
            const BULLET = '  - ';
            let emailBody = '';
            if (expirationDateString !== null || isBurnafterreading) {
                emailBody += I18n._('Notice:');
                emailBody += EOL;

                if (expirationDateString !== null) {
                    emailBody += EOL;
                    emailBody += BULLET;
                    // avoid DOMPurify mess with forward slash in expirationDateString
                    emailBody += Helper.sprintf(
                        I18n._(
                            'This link will expire after %s.',
                            '%s'
                        ),
                        expirationDateString
                    );
                }
                if (isBurnafterreading) {
                    emailBody += EOL;
                    emailBody += BULLET;
                    emailBody += I18n._(
                        'This link can only be accessed once, do not use back or refresh button in your browser.'
                    );
                }

                emailBody += EOL;
                emailBody += EOL;
            }
            emailBody += I18n._('Link:');
            emailBody += EOL;
            emailBody += $('#pasteurl').attr('href') || window.location.href; // href is tried first as it might have been shortened
            return emailBody;
        }

function sendToShortener()
        {
            if ($shortenButton.hasClass('buttondisabled')) {
                return;
            }
            $.ajax({
                type: 'GET',
                url: `${$shortenButton.data('shortener')}${encodeURIComponent($pasteUrl.attr('href'))}`,
                headers: {'Accept': 'text/html, application/xhtml+xml, application/xml, application/json'},
                processData: false,
                timeout: 10000,
                xhrFields: {
                    withCredentials: false
                },
                success: PasteStatus.extractUrl
            })
            .fail(function(data, textStatus, errorThrown) {
                console.error(textStatus, errorThrown);
                // we don't know why it failed, could be CORS of the external
                // server not setup properly, in which case we follow old
                // behavior to open it in new tab
                window.open(
                    `${$shortenButton.data('shortener')}${encodeURIComponent($pasteUrl.attr('href'))}`,
                    '_blank',
                    'noopener, noreferrer'
                );
            });
        }

function triggerEmailSend(emailBody)
        {
            window.open(
                `mailto:?body=${encodeURIComponent(emailBody)}`,
                '_self',
                'noopener, noreferrer'
            );
        }