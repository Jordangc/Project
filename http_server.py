import socket
import datetime
import threading

class HTTP_Server:
   class HTTP_Response(threading.Thread):
      def __init__(self):
        self.size = 1024
        self.web_dir = "C:/www"
        self.not_found = 'HTTP/1.1 404 Not Found\nServer: HTTPd/1.0 \n\n <HTML> <BODY> PAGE NOT FOUND </BODY> </HTML> \n\n'
     
      def respond(self, channel, details):
         print 'We have opened a connection with', details
		 
         http_request = channel.recv ( self.size )
         self.get_file_name(http_request)
		 
         req_time = datetime.datetime.now()
         response = 'HTTP/1.1 200 OK  \nDate: ' \
            + req_time.strftime("%a, %d %b  %Y %H:%M:%S") + \
            ' GMT\nExpires: -1\nCache-Control: private, max-age=0\nContent-Type: text/html; charset=UTF-8\nServer: JordanLayneMatt\nContent-Length: 438\nConnection: close\n\n' #
         print http_request
         print 'file being sent ', self.file_name
         try:
            file_to_send = open(self.web_dir + self.file_name, 'r')
            response += file_to_send.read() + '\n\n'
            file_to_send.close()
            print response
            channel.send (response)
         except IOError:
            channel.send (self.not_found)
         channel.close()
      
      def get_file_name(self, http_request):
         part1 = http_request.partition(' ')
         part2 = part1[2].partition(' ')
         self.file_name = part2[0]
         if self.file_name == '/':
            self.file_name = '/index.html'
     
     
   def __init__(self):
      self.host = ''
      self.port = 2727
      self.backlog = 5

      #authorization = 'HTTP/1.1 401 Authorization Required\nServer: HTTPd/1.0\nDate: ' + req_time.strftime("%a, %d %b  %Y %H:%M:%S") + ' GMT\nWWW-Authenticate: Basic realm="Secure Area"\nContent-Type: text/html\nContent-Length: 311\n\n<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"\n "http://www.w3.org/TR/1999/REC-html401-19991224/loose.dtd">\n<HTML>\n  <HEAD>\n    <TITLE>Error</TITLE>\n    <META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=ISO-8859-1">\n  </HEAD>\n  <BODY><H1>401 Unauthorized.</H1></BODY>\n</HTML>\n\n'
      self.server = None
   
   def run(self):
      self.server = socket.socket ( socket.AF_INET, socket.SOCK_STREAM )
      self.server.bind ( ( self.host, self.port ) )
      self.server.listen ( 1 )
      while True:
         response = self.HTTP_Response()
         channel, details = self.server.accept()
         response.respond(channel, details)
 
if __name__ == "__main__":
   s = HTTP_Server()
   s.run()